<p align="center"><img src="resources/images/logo.png?raw=true"></p>

<div dir=rtl>

# پیرو۲۴

این پکیج برای پرداخت انلاین توسط درگاه پرداخت پیرو در لاراول ایجاد شده است.

## نصب

نصب با استفاده از کامپوزر

</div>

``` bash
$ composer require payro/payment
```

<div dir="rtl">

## تنظیمات

درصورتی که از `Laravel 5.5` یا ورژن های بالاتر استفاده میکنید نیازی به انجام تنظیمات `providers` و `alias` نخواهید داشت.

درون فایل `config/app.php` دستورات زیر را وارد کنید

</div>

```php
# In your providers array.
'providers' => [
    ...
    Payro\Payment\Provider\PaymentServiceProvider::class,
],

# In your aliases array.
'aliases' => [
    ...
    'Payment' => Payro\Payment\Facade\Payment::class,
],
```

<div dir="rtl">

سپس تنظیمات درگاه پرداخت پیرو را انجام دهید

</div>

```php
'drivers' => [
        'payro24' => [
            'apiPurchaseUrl' => 'https://api.payro24.ir/v1.0/payment',
            'apiPaymentUrl' => 'https://payro24.ir/',
            'apiSandboxPaymentUrl' => 'https://payro24.ir/p/ws-sandbox/',
            'apiVerificationUrl' => 'https://api.payro24.ir/v1.0/payment/verify',
            'merchantId' => '',
            'callbackUrl' => 'http://yoursite.com/path/to',
            'description' => 'payment in '.config('app.name'),
            'sandbox' => false, // set it to true for test environments
        ]
]
```

<div dir="rtl">

## طریقه استفاده

در تمامی پرداخت ها اطلاعات پرداخت درون صورتحساب شما نگهداری میشود. برای استفاده از پکیج ابتدا نحوه ی استفاده از کلاس `Invoice` به منظور کار با صورتحساب ها را توضیح میدهیم.

#### کار با صورتحساب ها

قبل از انجام هرکاری نیاز به ایجاد یک صورتحساب دارید. برای ایجاد صورتحساب میتوانید از کلاس `Invoice` استفاده کنید.

درون کد خودتون به شکل زیر عمل کنید:

</div>

```php
# On the top of the file.
use Payro\Payment\Invoice;
...

# create new invoice
$invoice = new Invoice;

# set invoice amount
$invoice->amount(1000);
$invoice->detail(['name1' => 'detail1','name2' => 'detail2']);
```

<div dir="rtl">

متدهای موجود برای کار با صورتحساب ها:

- `uuid` : یک ایدی یونیک برای صورتحساب تنظیم میکند
- `getUuid` : ایدی یونیک صورتحساب را برمیگرداند
- `detail` : توضیحات یا مواردی که مرتبط به صورتحساب است را به صورتحساب اظافه میکند
- `getDetails` : تمامی موارد مرتبطی که به صورتحساب افزوده شده است را برمیگرداند
- `amount` : مقدار هزینه ای که باید پرداخت شود را مشخص میکند
- `getAmount` : هزینه ی صورتحساب را برمیگرداند
- `transactionId` : شماره تراکنش صورتحساب را مشخص میکند
- `getTransactionId` : شماره تراکنش صورتحساب را برمیگرداند
- `via` : درایوری که قصد پرداخت صورتحساب با آن را داریم مشخص میکند
- `getDriver` : درایور انتخاب شده را برمیگرداند

#### ثبت درخواست برای پرداخت صورتحساب
به منظور پرداخت تمامی صورتحساب ها به یک شماره تراکنش بانکی یا `transactionId` نیاز خواهیم داشت.
با ثبت درخواست به منظور پرداخت میتوان شماره تراکنش بانکی را دریافت کرد:

</div>

```php
# On the top of the file.
use Payro\Payment\Invoice;
use Payro\Payment\Facade\Payment;
...

# create new invoice
$invoice = (new Invoice)->amount(1000);

# purchase the given invoice
Payment::purchase($invoice,function($driver, $transactionId) {
	// we can store $transactionId in database
});

# purchase method accepts a callback function
Payment::purchase($invoice, function($driver, $transactionId) {
    // we can store $transactionId in database
});

# you can specify callbackUrl
Payment::callbackUrl('http://yoursite.com/verify')->purchase(
    $invoice, 
    function($driver, $transactionId) {
    	// we can store $transactionId in database
	}
);
```

<div dir="rtl">

#### پرداخت صورتحساب

با استفاده از شماره تراکنش یا `transactionId` میتوانیم کاربر را به صفحه ی پرداخت بانک هدایت کنیم:

</div>

```php
# On the top of the file.
use Payro\Payment\Invoice;
use Payro\Payment\Facade\Payment;
...

# create new invoice
$invoice = (new Invoice)->amount(1000);
# purchase and pay the given invoice
// you should use return statement to redirect user to the bank's page.
return Payment::purchase($invoice, function($driver, $transactionId) {
    // store transactionId in database, we need it to verify payment in future.
})->pay();

# do all things together a single line
return Payment::purchase(
    (new Invoice)->amount(1000), 
    function($driver, $transactionId) {
    	// store transactionId in database.
        // we need the transactionId to verify payment in future
	}
)->pay();
```

<div dir="rtl">


#### اعتبار سنجی پرداخت

بعد از پرداخت شدن صورتحساب توسط کاربر, بانک کاربر را به یکی از صفحات سایت ما برمیگردونه و ما با اعتبار سنجی میتونیم متوجه بشیم کاربر پرداخت رو انجام داده یا نه!

</div>

```php
# On the top of the file.
use Payro\Payment\Facade\Payment;
use Payro\Payment\Exceptions\Invalpayro24mentException;
...

# you need to verify the payment to insure the invoice has been paid successfully
// we use transaction's id to verify payments
// its a good practice to add invoice's amount.
try {
	$receipt = Payment::amount(1000)->transactionId($transaction_id)->verify();

    // you can show payment's referenceId to user
    echo $receipt->getReferenceId();    

    ...
} catch (Invalpayro24mentException $exception) {
    /**
    	when payment is not verified , it throw an exception.
    	we can catch the excetion to handle invalid payments.
    	getMessage method, returns a suitable message that can be used in user interface.
    **/
    echo $exception->getMessage();
}
```

<div dir="rtl">


در صورتی که پرداخت توسط کاربر به درستی انجام نشده باشه یک استثنا از نوع `InvalidPaymentException` ایجاد میشود که حاوی پیام متناسب با پرداخت انجام شده است.

#### متدهای سودمند

- `callbackUrl` : با استفاده از این متد به صورت داینامیک میتوانید ادرس صفحه ای که بعد از پرداخت انلاین کاربر به ان هدایت میشود را مشخص کنید

</div>

```php
  # On the top of the file.
  use Payro\Payment\Invoice;
  use Payro\Payment\Facade\Payment;
  ...
  
  # create new invoice
  $invoice = (new Invoice)->amount(1000);
  
  # purchase the given invoice
  Payment::callbackUrl($url)->purchase(
      $invoice, 
      function($driver, $transactionId) {
      // we can store $transactionId in database
  	}
  );
```

<div dir="rtl">

- `amount` : به کمک این متد میتوانید به صورت مستقیم هزینه صورتحساب را مشخص کنید

</div>

```php
  # On the top of the file.
  use Payro\Payment\Invoice;
  use Payro\Payment\Facade\Payment;
  ...
  
  # purchase (we set invoice to null)
  Payment::callbackUrl($url)->amount(1000)->purchase(
      null, 
      function($driver, $transactionId) {
      // we can store $transactionId in database
  	}
  );
```

<div dir="rtl">

#### رویدادها

شما میتوانید درون برنامه خود دو رویداد را ثبت و ضبط کنید

- **InvoicePurchasedEvent** : هنگامی که یک پرداخت به درستی ثبت شود این رویداد اتفاق میافتد.
- **InvoiceVerifiedEvent** : هنگامی که یک پرداخت به درستی وریفای شود این رویداد اتفاق میافتد

</div>

