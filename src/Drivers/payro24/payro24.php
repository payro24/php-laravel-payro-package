<?php

namespace Payro\Payment\Drivers\payro24;

use GuzzleHttp\Client;
use Payro\Payment\Abstracts\Driver;
use Payro\Payment\Exceptions\InvalidPaymentException;
use Payro\Payment\Exceptions\PurchaseFailedException;
use Payro\Payment\Contracts\ReceiptInterface;
use Payro\Payment\Invoice;
use Payro\Payment\Receipt;

class payro24 extends Driver
{
    /**
     * payro24 Client.
     *
     * @var object
     */
    protected $client;

    /**
     * Invoice
     *
     * @var Invoice
     */
    protected $invoice;

    /**
     * Driver settings
     *
     * @var object
     */
    protected $settings;

    /**
     * payro24 constructor.
     * Construct the class with the relevant settings.
     *
     * @param Invoice $invoice
     * @param $settings
     */
    public function __construct(Invoice $invoice, $settings)
    {
        $this->invoice($invoice);
        $this->settings = (object)$settings;
        $this->client = new Client();
    }

    /**
     * Purchase Invoice.
     *
     * @return string
     *
     * @throws PurchaseFailedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function purchase()
    {
        $details = $this->invoice->getDetails();

//        $name = '';
//        if (isset($details['name'])) {
//            $name = $details['name'];
//        }

//        $mail = null;
//        if (!empty($details['mail'])) {
//            $mail = $details['mail'];
//        } elseif (!empty($details['email'])) {
//            $mail = $details['email'];
//        }
//
//        $desc = $this->settings->description;
//        if (!empty($details['desc'])) {
//            $desc = $details['desc'];
//        } elseif (!empty($details['description'])) {
//            $desc = $details['description'];
//        }

        $data = array(
            'order_id' => $this->invoice->getUuid(),
            'amount' => $this->invoice->getAmount(),
            'callback' => $this->settings->callbackUrl,
        );

//        if (!isset($name)) $data['name'] = $name;
//        if (isset($phone)) $data['phone'] = $phone;
//        if (isset($mail)) $data['mail'] = $mail;
//        if (isset($desc)) $data['desc'] = $desc;

        $response = $this
            ->client
            ->request(
                'POST',
                $this->settings->apiPurchaseUrl,
                [
                    "headers" => [
                        'P-TOKEN' => $this->settings->merchantId,
                        'Content-Type' => 'application/json',
                        'P-SANDBOX' => (int)$this->settings->sandbox,
                    ],
                    "json" => $data,
                    "http_errors" => false,
                ]
            );

        $body = json_decode($response->getBody()->getContents(), true);

        var_dump($body);
        exit();
        if (empty($body['id'])) {
            // error has happened
            $message = $body['error_message'] ?? 'خطا در هنگام درخواست برای پرداخت رخ داده است.';
            throw new PurchaseFailedException($message);
        }

        $this->invoice->transactionId($body['id']);

        // return the transaction's id
        return $this->invoice->getTransactionId();
    }

    /**
     * Pay the Invoice
     *
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function pay()
    {
        $apiUrl = $this->settings->apiPaymentUrl;

        // use sandbox url if we are in sandbox mode
        if (!empty($this->settings->sandbox)) {
            $apiUrl = $this->settings->apiSandboxPaymentUrl;
        }

        $payUrl = $apiUrl . $this->invoice->getTransactionId();

        // redirect using laravel logic
        return redirect()->to($payUrl);
    }

    /**
     * Verify payment
     *
     * @return mixed|void
     *
     * @throws InvalidPaymentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verify(): ReceiptInterface
    {
        $data = [
            'id' => $this->invoice->getTransactionId() ?? request()->input('id'),
            'order_id' => request()->input('order_id'),
        ];

        $response = $this->client->request(
            'POST',
            $this->settings->apiVerificationUrl,
            [
                'json' => $data,
                "headers" => [
                    'P-TOKEN' => $this->settings->merchantId,
                    'Content-Type' => 'application/json',
                    'P-SANDBOX' => (int)$this->settings->sandbox,
                ],
                "http_errors" => false,
            ]
        );
        $body = json_decode($response->getBody()->getContents(), true);

        if (isset($body['error_code']) || $body['status'] != 100) {
            $errorCode = $body['status'] ?? $body['error_code'];

            $this->notVerified($errorCode);
        }

        return $this->createReceipt($body['track_id']);
    }

    /**
     * Generate the payment's receipt
     *
     * @param $referenceId
     *
     * @return Receipt
     */
    protected function createReceipt($referenceId)
    {
        $receipt = new Receipt('payro24', $referenceId);

        return $receipt;
    }

    /**
     * Trigger an exception
     *
     * @param $status
     *
     * @throws InvalidPaymentException
     */
    private function notVerified($status)
    {
        $translations = array(
            "1" => "پرداخت انجام نشده است.",
            "2" => "پرداخت ناموفق بوده است.",
            "3" => "خطا رخ داده است.",
            "4" => "بلوکه شده.",
            "5" => "برگشت به پرداخت کننده.",
            "6" => "برگشت خورده سیستمی.",
            "10" => "در انتظار تایید پرداخت.",
            "100" => "پرداخت تایید شده است.",
            "101" => "پرداخت قبلا تایید شده است.",
            "200" => "به دریافت کننده واریز شد.",
            "11" => "کاربر مسدود شده است.",
            "12" => "API Key یافت نشد.",
            "13" => "درخواست شما از {ip} ارسال شده است. این IP با IP های ثبت شده در وب سرویس همخوانی ندارد.",
            "14" => "وب سرویس تایید نشده است.",
            "21" => "حساب بانکی متصل به وب سرویس تایید نشده است.",
            "31" => "کد تراکنش id نباید خالی باشد.",
            "32" => "شماره سفارش order_id نباید خالی باشد.",
            "33" => "مبلغ amount نباید خالی باشد.",
            "34" => "مبلغ amount باید بیشتر از {min-amount} ریال باشد.",
            "35" => "مبلغ amount باید کمتر از {max-amount} ریال باشد.",
            "36" => "مبلغ amount بیشتر از حد مجاز است.",
            "37" => "آدرس بازگشت callback نباید خالی باشد.",
            "38" => "درخواست شما از آدرس {domain} ارسال شده است. دامنه آدرس بازگشت callback با آدرس ثبت شده در وب سرویس همخوانی ندارد.",
            "51" => "تراکنش ایجاد نشد.",
            "52" => "استعلام نتیجه ای نداشت.",
            "53" => "تایید پرداخت امکان پذیر نیست.",
            "54" => "مدت زمان تایید پرداخت سپری شده است.",
        );
        if (array_key_exists($status, $translations)) {
            throw new InvalidPaymentException($translations[$status]);
        } else {
            throw new InvalidPaymentException('خطای ناشناخته ای رخ داده است.');
        }
    }
}
