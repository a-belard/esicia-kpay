<?php
$msisdn = '';
$email = '';
$cname = '';

function exchange($currency, $amount)
{
  // call this for exchange in rwf https://esicia.rw/fx/?curr=USD
  /**
   * {"lastmodified":"2024-06-25 10:15:04","fx":{"date":"2024-06-25","name":"USD","buy":"1,296.420742","sell":"1,322.346538","middle":"1,309.383640"}}
   */
  if ($currency === "RWF" || $currency === "Fr" || $currency === "Frw" || $currency === "RF") {
    return $amount;
  }
  if ($currency === "Rs") {
    $currency = "INR";
  }
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://esicia.rw/fx/?curr=$currency");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  $response = curl_exec($ch);
  curl_close($ch);
  $data = json_decode($response);
  $rate = $data->fx->sell;
  // parse the exchange rate
  $rate = str_replace(',', '', $rate);
  $rate = str_replace($currency, '', $rate);
  $rate = str_replace(' ', '', $rate);
  $rate = str_replace(' ', '', $rate);
  $rate = str_replace('-', '', $rate);
  $rate = str_replace('(', '', $rate);
  return $amount * $rate;
}

// if post
if ($_POST) {
  $jsonData = $_POST;
  // check for initial settings
  if (!isset(get_option('kpay_plugin_options')['environment']) || !isset(get_option('kpay_plugin_options')['username']) || !isset(get_option('kpay_plugin_options')['password']) || !isset(get_option('kpay_plugin_options')['retailerid']) || !isset(get_option('kpay_plugin_options')['returl']) || !isset(get_option('kpay_plugin_options')['redirecturl'])) {
    echo "<script>alert('Please set the plugin settings first.')</script>";
  } else {
    $postData = [];
    $postData["msisdn"] = $jsonData["msisdn"];
    $postData["details"] = isset(get_option('kpay_plugin_options')['details']) ? get_option('kpay_plugin_options')['details'] : json_encode('');
    $postData["refid"] = rand(1000000000000000, 9999999999999999);
    $postData["retailerid"] = isset(get_option('kpay_plugin_options')['retailerid']) ? get_option('kpay_plugin_options')['retailerid'] : json_encode('');
    $postData["returl"] = isset(get_option('kpay_plugin_options')['returl']) ? get_option('kpay_plugin_options')['returl'] : json_encode('');
    $postData["redirecturl"] = isset(get_option('kpay_plugin_options')['redirecturl']) ? get_option('kpay_plugin_options')['redirecturl'] : json_encode('');
    $postData["amount"] = (int) $jsonData["amount"];
    $postData["currency"] = $jsonData["curr"];
    $postData["amount"] = exchange($jsonData["curr"], $jsonData["amount"]);
    $postData["currency"] = "RWF";
    $postData["pmethod"] = $jsonData["payment_method"];
    $postData["cnumber"] = isset($jsonData["paymentMethodInput"]) ? $jsonData["paymentMethodInput"] : $jsonData["msisdn"];
    $postData["email"] = $jsonData["email"];
    $postData["cname"] = $jsonData["cname"];
    $bankid = "";
    if ($postData["pmethod"] === "momo") {
      if ($postData["msisdn"][5] == "8" || $postData["msisdn"][5] == "9") {
        $bankid = '63510';
      } else {
        $bankid = '63514';
      }
    } else if ($postData["pmethod"] === "spenn") {
      $bankid = '63502';
    } else if ($postData["pmethod"] === "bank") {
      $bankid = '192';
    } else if ($postData["pmethod"] === "cc" || $postData["pmethod"] === "smartcash") {
      $bankid = '000';
    }
    $postData["bankid"] = $bankid;
    $postData = json_encode($postData);

    $api_url = '';
    $environment = get_option('kpay_plugin_options')['environment'];
    if ($environment == 'Test') {
      $api_url = 'pay.esicia.com';
    } else {
      $api_url = 'pay.esicia.rw';
    }

    $ch = curl_init("https://$api_url");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
      $ch,
      CURLOPT_HTTPHEADER,
      array(
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($postData),
      )
    );
    curl_setopt($ch, CURLOPT_USERPWD, get_option('kpay_plugin_options')['username'] . ":" . get_option('kpay_plugin_options')['password']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $json_result = json_decode($result);

    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($status_code != 200) {
      curl_close($ch);
      echo "<script>alert('$json_result->message')</script>";
    } else {
      if ($jsonData["payment_method"] == "cc") {
        echo "<script>window.location.href = '$json_result->url'</script>";
      } else {
        echo "<script>alert('Dial *182*7*1# to approve the payment.')</script>";
      }
    }

    curl_close($ch);
  }
} else if ($_GET) {
  $msisdn = isset($_GET['msisdn']) ? $_GET['msisdn'] : '';
  $email = isset($_GET['email']) ? $_GET['email'] : '';
  $cname = isset($_GET['cname']) ? $_GET['cname'] : '';
}
?>

<form action="" method="post" class="flex flex-column" id="kpay-form">
  <h3 style="text-align:center">KPay</h3>
  <div class="money-container">
    <div class=" amount-container">
      <label for="amount">Amount</label>
      <input type="number" class="form-control" id="amount" name="amount" placeholder="Enter the amount" required>
    </div>
    <div class=" currency-container">
      <label for="curr">Currency</label>
      <select class="form-control" id="curr" name="curr">
        <option value="RWF" selected>RWF</option>
        <option value="USD">USD</option>
      </select>
    </div>
  </div>
  <div class="">
    <label for="mobile">Phone</label>
    <input type="text" class="form-control" id="msisdn" name="msisdn" default="<?= $msisdn ?>" placeholder="Enter your phone" required />
  </div>
  <div class="">
    <label for="email">Email address</label>
    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required default="<?php echo $email; ?>" />
  </div>
  <div class="">
    <label for="name">Name</label>
    <input type="text" class="form-control" id="cname" name="cname" placeholder="Enter your name" required default="<?php echo $cname; ?>" />
  </div>
  <div>
    <label for="name">Payment Method</label>
    <div class="payment_methods">
      <div>
        <input type="radio" id="momo" name="payment_method" value="momo" checked hidden>
        <label for="momo" class="method_radio_label active">
          <img src="<?php echo plugin_dir_url(__FILE__) . 'images/momo.png'; ?>" alt="momo" class="method_icons"> |
          <img src="<?php echo plugin_dir_url(__FILE__) . 'images/airtel.svg'; ?>" alt="momo" class="method_icons">
        </label>
      </div>
      <div>
        <input type="radio" id="card" name="payment_method" value="cc" hidden>
        <label for="card" class="method_radio_label">
          <img src="<?php echo plugin_dir_url(__FILE__) . 'images/visa.svg'; ?>" alt="momo" class="method_icons"> |
          <img src="<?php echo plugin_dir_url(__FILE__) . 'images/mastercard.svg'; ?>" alt="momo" class="method_icons"> |
          <img src="<?php echo plugin_dir_url(__FILE__) . 'images/smartcash.svg'; ?>" alt="momo" class="method_icons">
        </label>
      </div>
      <div>
        <input type="radio" id="spenn" name="payment_method" value="spenn" hidden>
        <label for="spenn" class="method_radio_label">
          <img src="<?php echo plugin_dir_url(__FILE__) . 'images/spenn.svg'; ?>" alt="momo" class="method_icons">
        </label>
      </div>
    </div>
  </div>
  <div class="" id="paymentMethodInput">
    <label for="paymentMethodInput">Payment number</label>
    <input type="text" class="form-control" id="paymentMethodInput" name="paymentMethodInput" placeholder="Enter your payment number" required>
  </div>

  <button type="submit" class="btn btn-primary">Submit</button>
</form>


<script>
  document.addEventListener("DOMContentLoaded", function() {
    let kpayForm = document.getElementById("kpay-form");

    // add onchange handler to payment method radio buttons
    $('input[type=radio][name=payment_method]').change(function() {
      if (this.value === 'momo') {
        document.getElementById("paymentMethodInput").innerHTML = `
          <label for="paymentMethodInput">Payment number</label>
          <input type="text" class="form-control" id="paymentMethodInput" name="paymentMethodInput" placeholder="Enter your payment number" required>
        `;
        $(".method_radio_label").removeClass("active");
        $("label[for='momo']").addClass("active");
      } else if (this.value === 'spenn') {
        document.getElementById("paymentMethodInput").innerHTML = `
          <label for="paymentMethodInput">SPENN number</label>
          <input type="text" class="form-control" id="paymentMethodInput" name="paymentMethodInput" placeholder="Enter your SPENN number" required>
        `;
        $(".method_radio_label").removeClass("active");
        $("label[for='spenn']").addClass("active");
      } else if (this.value === 'cc') {
        document.getElementById("paymentMethodInput").innerHTML = ""
        $(".method_radio_label").removeClass("active");
        $("label[for='card']").addClass("active");
      } else if (this.value === 'smartcash') {
        document.getElementById("paymentMethodInput").innerHTML = ""
        $(".method_radio_label").removeClass("active");
        $("label[for='smartcash']").addClass("active");
      } else {
        $('#paymentMethodInput').html('');
      }
    });
  });
</script>