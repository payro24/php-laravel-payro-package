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

        $name = '';
        if (isset($details['name'])) {
            $name = $details['name'];
        }

        $mail = null;
        if (!empty($details['mail'])) {
            $mail = $details['mail'];
        } elseif (!empty($details['email'])) {
            $mail = $details['email'];
        }

        $desc = $this->settings->description;
        if (!empty($details['desc'])) {
            $desc = $details['desc'];
        } elseif (!empty($details['description'])) {
            $desc = $details['description'];
        }

        $data = array(
            'order_id' => $this->invoice->getUuid(),
            'amount' => $this->invoice->getAmount(),
            'callback' => $this->settings->callbackUrl,
            'name' => '',
            'phone' => '',
            'mail' => '',
            'desc' => '',
        );

        if (!isset($name)) $data['name'] = $name;
        if (isset($phone)) $data['phone'] = $phone;
        if (isset($mail)) $data['mail'] = $mail;
        if (isset($desc)) $data['desc'] = $desc;

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
                    "http_errors" => false,
                    "json" => $data
                ]
            );

        $body = json_decode($response->getBody()->getContents(), true);
        if (empty($body['id'])) {
            // error has happened
            $message = $body['error_message'] ?? '?????? ???? ?????????? ?????????????? ???????? ???????????? ???? ???????? ??????.';
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
            "1" => "???????????? ?????????? ???????? ??????.",
            "2" => "???????????? ???????????? ???????? ??????.",
            "3" => "?????? ???? ???????? ??????.",
            "4" => "?????????? ??????.",
            "5" => "?????????? ???? ???????????? ??????????.",
            "6" => "?????????? ?????????? ????????????.",
            "10" => "???? ???????????? ?????????? ????????????.",
            "100" => "???????????? ?????????? ?????? ??????.",
            "101" => "???????????? ???????? ?????????? ?????? ??????.",
            "200" => "???? ???????????? ?????????? ?????????? ????.",
            "11" => "?????????? ?????????? ?????? ??????.",
            "12" => "API Key ???????? ??????.",
            "13" => "?????????????? ?????? ???? {ip} ?????????? ?????? ??????. ?????? IP ???? IP ?????? ?????? ?????? ???? ???? ?????????? ?????????????? ??????????.",
            "14" => "???? ?????????? ?????????? ???????? ??????.",
            "21" => "???????? ?????????? ???????? ???? ???? ?????????? ?????????? ???????? ??????.",
            "31" => "???? ???????????? id ?????????? ???????? ????????.",
            "32" => "?????????? ?????????? order_id ?????????? ???????? ????????.",
            "33" => "???????? amount ?????????? ???????? ????????.",
            "34" => "???????? amount ???????? ?????????? ???? {min-amount} ???????? ????????.",
            "35" => "???????? amount ???????? ???????? ???? {max-amount} ???????? ????????.",
            "36" => "???????? amount ?????????? ???? ???? ???????? ??????.",
            "37" => "???????? ???????????? callback ?????????? ???????? ????????.",
            "38" => "?????????????? ?????? ???? ???????? {domain} ?????????? ?????? ??????. ?????????? ???????? ???????????? callback ???? ???????? ?????? ?????? ???? ???? ?????????? ?????????????? ??????????.",
            "51" => "???????????? ?????????? ??????.",
            "52" => "?????????????? ?????????? ???? ??????????.",
            "53" => "?????????? ???????????? ?????????? ???????? ????????.",
            "54" => "?????? ???????? ?????????? ???????????? ???????? ?????? ??????.",
        );
        if (array_key_exists($status, $translations)) {
            throw new InvalidPaymentException($translations[$status]);
        } else {
            throw new InvalidPaymentException('???????? ???????????????? ???? ???? ???????? ??????.');
        }
    }
}
