<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="<?php echo asset('landlord/css/output.css') ?>" rel="stylesheet">

  <style>
    @media print {
      @page {
        size: A4;
      }

      .page-break {
        page-break-before: always;
      }
    }
  </style>
</head>

<body>
  <div style="display: flex; justify-content: center; align-items: center">
    <button style="
          margin: 0.25rem;
          background-color: #4caf50;
          color: white;
          padding: 0.25rem 0.5rem;
        " onclick="window.history.back();" class="print:hidden">
      Back
    </button>
    <button style="
          margin: 0.25rem;
          background-color: #4caf50;
          color: white;
          padding: 0.25rem 0.5rem;
        " onclick="window.print();" class="print:hidden">
      Print
    </button>
  </div>
  <div class="page border border-black print:border-0 h-fit w-[210mm] print:w-[210mm] mb-4 mx-auto p-[5px]">
    <div class="flex justify-between print:border-black border-b pb-2">
    <div class="logo w-[30%]">
      @php
        $siteLogo = 'logo.png';
        if (isset($general_setting) && optional($general_setting)->site_logo) {
        $siteLogo = optional($general_setting)->site_logo;
        }
        $logoUrl = asset('landlord/images/'.$siteLogo);
        if (!empty($reseller) && !empty($reseller->company_logo)) {
          $resellerPath = public_path('landlord/resellers/company_logo/'.$reseller->company_logo);
          if (file_exists($resellerPath) && is_file($resellerPath)) {
            $logoUrl = asset('landlord/resellers/company_logo/'.$reseller->company_logo);
          }
        }
      @endphp
      <img src="{{ $logoUrl }}"
         alt="logo"
         class="h-[80px]"
         onerror="this.onerror=null;this.src='{{ asset('landlord/images/logo.png') }}'">
    </div>
      <div class="title w-[40%] flex justify-center items-center">
        <h1 class="text-2xl font-bold uppercase px-4 py-2 bg-blue-300 rounded-full">Proposal Letter</h1>
      </div>
      <div class="company_info text-right w-[30%]">
        <p class="text-lg font-bold">{{$reseller->company ?? 'Sherazi Pos'}}</p>
        <p class="text-sm">{{$reseller->address ?? 'House # 13, Road # 3/F, Sector # 9, Uttara, Dhaka-1230'}}</p>
        <p class="text-sm">Phone: {{$reseller->phone ?? '+8801711 253028'}}</p>
        <p class="text-sm">Email: <a href="mailto:{{$reseller->email ?? 'contact@sheraziit.com'}}">{{$reseller->email ?? 'contact@sheraziit.com'}}</a></p>
      </div>
    </div>
    <!-- <div class="flex justify-between bg-gray-200 px-2 mt-2">
            <p class="text-sm">Agreement paper by company</p>
            <p class="text-sm">This proposal is valid for 30 days</p>
        </div> -->
    <div class="flex justify-between mt-3">
      <div class="customer_info">
        <p class="text-sm print:text-[12pt]">Date: {{date('jS F Y', strtotime($proposal->created_at))}}</p>
        <p class="text-sm print:text-[12pt]">To,</p>
        <p class="text-md font-bold">{{$proposal->customer_name}}</p>
        <p class="text-sm print:text-[12pt]">{{$proposal->customer_address}}</p>
        <p class="text-sm print:text-[12pt]">Phone: {{$proposal->customer_phone}}</p>
        <p class="text-sm print:text-[12pt]">Email: <a href="mailto:{{$proposal->customer_email}}">{{$proposal->customer_email}}</a></p>
      </div>
      <div class="proposal_info text-right">
        <p class="text-sm print:text-[12pt]">Proposal No: {{$proposal->proposal_number}}</p>
        <p class="text-sm print:text-[12pt]">Date: {{date('jS F Y', strtotime($proposal->created_at))}}</p>
        <p class="text-sm print:text-[12pt]">Validity: {{$proposal->validity}}</p>
      </div>
    </div>
    <div class="main_letter">
      <p class="text-sm print:text-[14pt] font-bold mt-4">Subject: Proposal for {{ optional($proposal->packageInfo)->name ?? 'N/A' }} package of POS Software</p>
      <p class="text-sm print:text-[12pt] font-bold mt-4">Dear Sir/Madam,</p>
      <p class="text-sm print:text-[12pt] justify">
        We appreciate your interest in finding a digital solution to support your business operations. Based on your
        requirements, we are pleased to submit our proposal for a business automation solution software. This software
        is designed to streamline your business operations by automating tasks, improving efficiency, and reducing
        costs.
        <br>
        A detailed overview of the software features, along with the terms and conditions of the agreement, is provided
        on the following pages.
      </p>
    </div>
    <div class="mt-3">
      <h2 class="text-[12pt] text-center font-semibold mb-4 print:text-[14pt]">Financial Proposal</h2>
  @php
    $reg = floatval($proposal->registration_fee ?? 0);
    $discount = 0;
    $discountValue = floatval($proposal->discount ?? $proposal->discount_value ?? 0);
    $discountType = strtolower(trim($proposal->discount_type ?? ''));
    if ($discountValue > 0) {
        $discount = ($discountType === 'percentage' || $discountType === 'percent' || $discountType === '%')? ($reg * $discountValue / 100) : $discountValue;
    }
@endphp


      <div class="overflow-x-auto w-full">
        <table class="w-full border border-gray-300 text-sm print:text-[12pt]">
          <thead class="bg-gray-100">
            <tr>
              <th class="border border-gray-300 px-4 py-2 text-left">Item</th>
              <th class="border border-gray-300 px-2 py-2 text-right">Unit Price</th>
              <th class="border border-gray-300 px-4 py-2 text-right">Quantity</th>
              <th class="border border-gray-300 px-4 py-2 text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="border border-gray-300 px-4 py-2">{{ optional($proposal->packageInfo)->name ?? 'N/A' }} package of POS Software (One-time license)</td>
              <td class="border border-gray-300 px-4 py-2 text-right">{{number_format($reg, 2)}}</td>
              <td class="border border-gray-300 px-4 py-2 text-right">1</td>
              <td class="border border-gray-300 px-4 py-2 text-right">{{number_format($reg, 2)}}</td>
            </tr>
            <tr>
              <td class="border border-gray-300 px-4 py-2">Monthly Subscription <br>
              <span>Monthly renewal fee: {{number_format($proposal->monthly, 2)}} and Yearly renewal fee: {{number_format($proposal->yearly, 2)}}</span></td>
              <td class="border border-gray-300 px-4 py-2 text-right">{{number_format($proposal->subscription_fee, 2)}}</td>
              <td class="border border-gray-300 px-4 py-2 text-right">1</td>
              <td class="border border-gray-300 px-4 py-2 text-right">{{number_format($proposal->subscription_fee, 2)}}</td>
            </tr>
            <tr class="font-semibold bg-gray-50">
              <td colspan="3" class="border border-gray-300 px-4 py-2 text-right">Total</td>
              <td class="border border-gray-300 px-4 py-2 text-right">{{number_format($reg + $proposal->subscription_fee, 2)}}</td>
            </tr>
            @if ($discount > 0)
              <tr class="font-semibold bg-gray-50">
                <td colspan="3" class="border border-gray-300 px-4 py-2 text-right">Discount</td>
                <td class="border border-gray-300 px-4 py-2 text-right">{{number_format($discount, 2)}}</td>
              </tr>
            @endif
            <tr class="font-semibold bg-gray-50">
              <td colspan="3" class="border border-gray-300 px-4 py-2 text-right">Grand Total</td>
              <td class="border border-gray-300 px-4 py-2 text-right">{{number_format($reg + $proposal->subscription_fee - $discount, 2)}}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <p class="text-sm mt-3 print:text-[12pt]">
      For your convenience, we’ve provided the demo access credentials below so that you can explore the core features
      of the software firsthand.
      <br>
      Please do not hesitate to contact us if you have any questions or require further information.
    </p>

    <section class="mt-4 print:mt-8 print:text-[12pt]">
      <div class="grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-2">

        <!-- Left Side: Signature -->
        <div class="break-inside-avoid mt-8">
          <p class="mb-2">Sincerely,</p>
          <p class="font-semibold">{{ucfirst($reseller->name ?? 'Md. Ismail Hossain')}}</p>
          <p>{{ucfirst($reseller->company ?? 'Sherazi POS')}}</p>
          <p>Phone: {{$reseller->phone ?? '+8801711 253028'}}</p>
          <p>Email: {{$reseller->email ?? 'contact@sheraziit.com'}}</p>
        </div>

        <!-- Right Side: Demo Access with QR -->
        <div
          class="rounded flex justify-start items-center border border-gray-300 p-2 bg-gray-50 gap-4 text-sm print:flex print:items-center print:justify-between print:gap-4 break-inside-avoid">

          <div class="text-[12pt]" style="max-width: 60%;">
            <p><strong>Link:</strong>
              <a style="text-wrap: wrap;" href="{{ $proposal->demo_link ?? 'https://demo.sherazipos.com'}}" target="_blank" class="text-blue-600 underline">
                {{ $proposal->demo_link ?? 'https://demo.sherazipos.com'}}
              </a>
            </p>
            <p><strong>Username:</strong> {{$proposal->username ?? 'demo'}}</p>
            <p><strong>Password:</strong> {{$proposal->password ?? 'demo'}}</p>
          </div>

          <div class="border border-gray-300 p-1 rounded shadow-md flex flex-col items-center">
            <img
                  src="https://api.qrserver.com/v1/create-qr-code/?data={{ $proposal->demo_link ?? 'https://demo.sherazipos.com'}}&size=120x120"
                  alt="Demo QR Code"
                  class="w-32 h-32 object-cover">
            <span class="text-sm text-gray-600">Demo QR Code</span>
          </div>

        </div>
      </div>
    </section>
    <div
      class="w-full bg-white border-t py-1 text-center text-xs text-gray-600 print:text-[10pt] print:w-full" style="margin-top: 10px;">
      <p>
        This proposal is confidential and intended solely for the recipient. Please do not share without prior consent.
      </p>
    </div>
  </div>
  <div class="page-break"></div>
  <div class="page border border-black print:border-0 h-fit w-[210mm] print:w-[210mm] mb-4 mx-auto p-[5px]">
    <div class="text-center text-[16pt] font-bold border-b">
      <h3>Software Registration & Subscription Agreement</h3>
    </div>
    <div class="mt-3 text-[14pt] print:text-[14pt]">
      <ol class="list-decimal ml-8">
        <li class="mb-2"><strong>চুক্তিপত্রের উদ্দেশ্য:</strong><br>
          <p>এই চুক্তির উদ্দেশ্য হলো, Business সফটওয়্যারের নির্দিষ্ট ফিচারসমূহ ও সেবা গ্রাহকের নির্বাচিত সাবস্ক্রিপশন
            প্যাকেজ অনুযায়ী সরবরাহ করা এবং গ্রাহক কর্তৃক নির্ধারিত ফি পরিশোধ সাপেক্ষে নিরবিচারে সফটওয়্যারটি ব্যবহারের
            অধিকার প্রদান।</p>
        </li>
        <li class="mb-2"><strong>চুক্তির মেয়াদ:</strong><br>
          <p>এই চুক্তি, চুক্তির তারিখ থেকে সর্বনিম্ন ২০ (বিশ) বছরের জন্য কার্যকর থাকবে, যদি না পূর্বে কোনো পক্ষ
            লিখিতভাবে বাতিল ঘোষণা করে।</p>
        </li>
        <li class="mb-2"><strong>ফি এবং পেমেন্ট শর্তাবলী:</strong><br>
          <ol class="list-decimal ml-6">
            <li class="mb-2">রেজিস্ট্রেশন ফি (এককালীন):<br>
              <p>গ্রাহককে একটি এককালীন রেজিস্ট্রেশন ফি প্রদান করতে হবে, যা সফটওয়্যারের জন্য ২০ বছরের আইনি অধিকার,
                Verified Batch, সিকিউরিটি, দীর্ঘমেয়াদী সফটওয়্যার রক্ষণাবেক্ষণ ও মনিটরিং চুক্তিবদ্ধতা, ট্রেইনিং এবং
                লং-টার্ম সাপোর্টের জন্য প্রযোজ্য হবে।</p>
            </li>
            <li class="mb-2">সাবস্ক্রিপশন ফি (মাসিক/বার্ষিক):<br>
              <p>এই ফি’র মাধ্যমে সফটওয়্যারের রিয়েলটাইম পরিচালনা এর জন্য ইনফ্রাস্ট্রাকচার মেইনটেন্যান্স, সার্ভার ব্যয়,
                ও প্রযুক্তিগত ব্যয়সমূহ নির্বাহ করা হবে।</p>
            </li>
          </ol>
        </li>
        <li class="mb-2"><strong>সফটওয়ারের সকল বৈশিষ্ট্য ও মডিউলসমূহ:</strong><br>
          <p>এই সফটওয়্যারটি একটি SaaS ভিত্তিক পূর্ণাঙ্গ ব্যবসায়িক ব্যবস্থাপনা প্ল্যাটফর্ম। এতে অন্তর্ভুক্ত রয়েছে এমন
            সব অত্যাধুনিক ফিচার ও কোর মডিউল যা একজন উদ্যোক্তা বা প্রতিষ্ঠানের দৈনন্দিন কার্যক্রমকে স্বয়ংক্রিয়, সংগঠিত
            এবং ডেটা-চালিত করে তোলে।</p>
            <p class="text-[12pt] font-bold mt-3 ml-6">সফটওয়্যারটিতে অন্তর্ভুক্ত মূল কার্যকরী বিভাগসমূহ (Core Modules)
              নিম্নরূপঃ</p>
            <div class="grid grid-cols-2 gap-4 text-[12pt] print:text-[12pt] mt-3">
              <ul class="list-disc ml-8">
                <li>ই-কমার্স পরিচালনা ব্যবস্থা</li>
                <li>POS Software ক্রয় বিক্রয় মডিউল</li>
                <li>ইনভেন্টরি বা মজুদ ব্যবস্থাপনা</li>
                <li>গ্রাহক ব্যবস্থাপনা (Customer Management)</li>
                <li>বাকি হিসাব ও আদায় ব্যবস্থা (Due Management)</li>
                <li>স্টক ব্যবস্থাপনা (Stock Control)</li>
                <li>ব্যয় ব্যবস্থাপনা (Expense Management)</li>
                <li>কোটেশন তৈরির অপশন</li>
                <li>অনলাইন ইনভয়েস তৈরি (Online Invoice Generation)</li>
                <li>পেমেন্ট নিশ্চিতকরণ রশিদ (Payment Confirmation Receipt)</li>
                <li>পণ্যের ইতিহাস সংরক্ষণ (Product History)</li>
                <li>দৈনিক নগদ হিসাব বন্ধকরণ (Cash Register Closing)</li>
                <li>রিটার্ন বা ফেরত ব্যবস্থাপনা (Return System)</li>
                <li>স্টক স্থানান্তর ব্যবস্থা (Transfer System)</li>
                <li>ক্রয় ইতিহাস (Purchase History)</li>
                <li>বিক্রয় ইতিহাস (Sales History)</li>
                <li>হাতে ডেটা ব্যাকআপ সংরক্ষণ ব্যবস্থা (Manual Data Backup)</li>
                <li>স্বয়ংক্রিয় রিপোর্ট আপডেট (Auto Report System)</li>
                <li>দ্রুত বাকি পরিশোধ অপশন (Quick Due Payment)</li>
                <li>দ্রুত পেমেন্ট গ্রহণ অপশন (Quick Payment Receive)</li>
                <li>বারকোড ও কিউআর প্রিন্ট সিস্টেম</li>
                <li>কুপন ব্যবস্থা (Coupon Management)</li>
                <li>উপহার কার্ড ব্যবস্থাপনা (Gift Card System)</li>
                <li>চালান কপি ইউআরএল (Copy Invoice URL)</li>
                <li>চালান প্রিন্টিং ব্যবস্থা (Print Challan Paper)</li>
                <li>স্বয়ংক্রিয় কুরিয়ার সংযোগ (Courier Integration)</li>
              </ul>
              <ul class="list-disc ml-6">
                <li>এসএমএস মার্কেটিং এবং বিজ্ঞপ্তি প্রেরণ ব্যবস্থা</li>
                <li>কর্মচারী অ্যাকাউন্ট ও ব্যবস্থাপনা</li>
                <li>হাজিরা ট্র্যাকিং ব্যবস্থা</li>
                <li>বেতন ব্যবস্থাপনা (Payroll System)</li>
                <li>হিসাব ও ব্যালেন্স পরিচালনা</li>
                <li>শাখা ব্যবস্থাপনা (Multiple Branch Control)</li>
                <li>গ্রাহক সম্পর্ক ব্যবস্থাপনা (CRM)</li>
                <li>নিজস্ব ডোমেইন সংযোগ ব্যবস্থা (Custom Domain Setup)</li>
                <li>সরবরাহকারী ব্যবস্থাপনা (Supplier Management)</li>
                <li>কুরিয়ার চালান প্রস্তুত (Courier Invoice)</li>
                <li>বিক্রয় চালান ব্যবস্থাপনা (Sales Invoice)</li>
                <li>সহায়তা টিকিট ব্যবস্থা (Support Ticket System)</li>
                <li>ই-কমার্স অর্ডার ট্র্যাকিং</li>
                <li>জালিয়াতি শনাক্তকরণ ব্যবস্থা (Fraud Checking System)</li>
                <li>মাল্টি-কারেন্সি সাপোর্ট</li>
                <li>মাল্টি-ভাষা ইন্টারফেস</li>
                <li>রিওয়ার্ড পয়েন্ট</li>
                <li>ব্যাচ/লট ট্র্যাকিং সুবিধা</li>
                <li>কাস্টম রিপোর্ট বিল্ডার</li>
                <li>থার্ড-পার্টি API ইন্টিগ্রেশন</li>
                <li>রোল-বেইজড অ্যাক্সেস কন্ট্রোল</li>
                <li>ডাইনামিক ডিসকাউন্ট ব্যবস্থাপনা</li>
                <li>মাল্টি-ওয়্যারহাউজ ম্যানেজমেন্ট</li>
                <li>ই-কমার্স ক্যাটাগরি ও কালেকশন সিস্টেম</li>
                <li>স্লাইডার এবং ব্যানার কনফিগারেশন</li>
                <li>সোশ্যাল মিডিয়া লিংক সেটআপ</li>
                <li>পেমেন্ট গেটওয়ে ইন্টিগ্রেশন</li>
                <li>শিপিং চার্জ কনফিগারেশন</li>
              </ul>
            </div>
            <p class="text-[12pt] mt-3">By Hand Data Backup System থাকায় আপনি নিজেই আপনার ডেটা নির্দিষ্ট সময়ে ডাউনলোড করে রাখতে পারবেন। </p>
            <p class="text-[12pt] font-bold mt-3">এই মডিউলগুলোর বিস্তারিত বর্ণনা ও কার্যকারিতা পর্যায়ক্রমে ব্যাখ্যা করা হলো:</p>
            <ul class="list-disc ml-6 text-[12pt]">
              <li>এই সফটওয়্যারটি একটি পূর্ণাঙ্গ ব্যবসায়িক সমাধান হিসেবে কাজ করে যেখানে <strong>E-commerce</strong> মডিউল ব্যবহার করে গ্রাহকরা তাদের পণ্যসমূহ অনলাইনে প্রদর্শন, বিক্রয়, এবং অর্ডার ট্র্যাক করতে পারেন।</li>
              <li>একই সাথে <strong>POS Software (Point of Sale)</strong> মডিউলের মাধ্যমে ফিজিক্যাল দোকান পরিচালনা, বিল জেনারেশন, ডিসকাউন্ট, এবং ক্যাশ হ্যান্ডলিং করা যায় সম্পূর্ণ রিয়েলটাইমে।</li>
              <li><strong>Inventory Management</strong> অংশে আপনি প্রোডাক্ট অ্যাড, স্টক অ্যাডজাস্টমেন্ট, স্টক কাউন্টিং, ও পণ্যের ইন ও আউট হিস্ট্রি রাখতে পারবেন, যা আপনাকে অর্ডার ম্যানেজমেন্ট ও সঠিক স্টক পূর্বাভাসে সহায়তা করবে।</li>
              <li><strong>Automated Courier Integration </strong>সিস্টেমের মাধ্যমে POS ও অনলাইন উভয় বিক্রয়ের অর্ডারসমূহ স্বয়ংক্রিয়ভাবে কুরিয়ার কোম্পানিতে প্রেরণ হবে এবং অর্ডার স্ট্যাটাস স্বয়ংক্রিয়ভাবে আপডেট হবে, যা সময় ও মানবসম্পদের সাশ্রয় করে।</li>
              <li><strong>Expense Management</strong> মডিউলের মাধ্যমে আপনি প্রতিটি ব্যয়ের শ্রেণীবিন্যাসসহ খরচ এন্ট্রি এবং রিপোর্ট দেখতে পারবেন, যা দৈনিক, মাসিক বা বাৎসরিক ব্যয় বিশ্লেষণে অত্যন্ত কার্যকর। </li>
              <li><strong>SMS Marketing</strong> এবং <strong>Automated SMS System</strong> ব্যবহার করে গ্রাহকদের কাছে মার্কেটিং ক্যাম্পেইন, অফার, এবং অর্ডার কনফার্মেশন পাঠানো যাবে।</li>
              <li><strong>HRM (Human Resource Management)</strong>, Employee Accounts, Attendance Tracking ও Salary Management-এর মাধ্যমে আপনি আপনার প্রতিষ্ঠানের মানবসম্পদ কার্যক্রম যেমন কর্মচারীর যোগদান, হাজিরা, ছুটি এবং মাসিক বেতন নির্ধারণ করে সহজেই পরিচালনা করতে পারবেন।</li>
              <li><strong>Accounts and Balances</strong> মডিউল আপনাকে অর্থনৈতিক লেনদেন, ব্যালেন্স অবস্থা, একাউন্ট স্টেটমেন্ট, ক্যাশ ইন/আউট এর উপর পূর্ণ নিয়ন্ত্রণ প্রদান করবে।</li>
              <li><strong>Branch Management</strong> সুবিধার মাধ্যমে আপনি একাধিক ব্রাঞ্চের হিসাব আলাদাভাবে রাখতে পারবেন এবং একই সফটওয়্যার থেকে কেন্দ্রীয়ভাবে সব শাখার রিপোর্ট মনিটর করতে পারবেন।</li>
              <li><strong>CRM</strong> মডিউলে কাস্টমার লিড, ফলোআপ, সোর্স ট্র্যাকিং, এবং কর্মদিবস অনুসারে অ্যাকশন প্ল্যান তৈরি করে বিক্রয় কার্যক্রম আরও ফলপ্রসূ করা সম্ভব হয়।</li>
              <li><strong>Custom Domain Setup</strong> এর মাধ্যমে আপনার নিজস্ব ডোমেইনে ই-কমার্স ওয়েবসাইট চালাতে পারবেন।</li>
              <li><strong>Supplier ও Customer Management</strong> মডিউল আপনাকে প্রতিটি সাপ্লায়ার এবং কাস্টমারের তথ্য সংরক্ষণ, ম্যানেজমেন্ট এবং পরিশোধ ইতিহাস ট্র্যাক করতে সাহায্য করবে।</li>
              <li><strong>Support Ticket System</strong>-এর মাধ্যমে যেকোনো কারিগরি সমস্যার জন্য আপনি সরাসরি টিকেট ওপেন করতে পারবেন এবং দ্রুত সহায়তা পেতে পারবেন।</li>
              <li><strong>Quotation Option</strong> মডিউলে আপনি কাস্টমারদের জন্য কোটেশন তৈরি করতে পারবেন এবং সেটি ইনভয়েসে কনভার্টও করতে পারবেন।</li>
              <li><strong>Invoice and Receipt</strong>, Courier Invoice, Sales Invoice, Online Invoice, ও Payment Confirmation Receipt সিস্টেম আপনাকে প্রতিটি লেনদেনের জন্য স্বয়ংক্রিয় ও প্রফেশনাল দলিল তৈরির সুবিধা দেয়।</li>
              <li><strong>Cash Register</strong> মডিউলে প্রতিদিনের লেনদেনের সমাপ্তি এবং ব্যালেন্স ক্লোজিংয়ের জন্য নির্দিষ্ট প্রক্রিয়া রয়েছে।</li>
              <li><strong>Return System</strong>-এর মাধ্যমে Sales এবং Purchase এর রিটার্ন কার্যক্রম সহজেই সম্পন্ন করা যায়।</li>
              <li><strong>Transfer System</strong> আপনাকে বিভিন্ন শাখা বা ওয়্যারহাউজের মধ্যে প্রোডাক্ট স্থানান্তর করতে দেয়।</li>
              <li><strong>Purchase</strong> এবং Sales Product History আপনাকে পুরনো লেনদেন বিশ্লেষণে সহায়তা করে।</li>
              <li><strong>Auto Report Update</strong> অপশন থাকায় কোনো ম্যানুয়াল ইনপুট ছাড়াই প্রতিদিনের গুরুত্বপূর্ণ রিপোর্ট সিস্টেম নিজে আপডেট করে রাখবে।</li>
              <li><strong>Quick Due Payment</strong> এবং Payment Receive সিস্টেম থাকায় আপনি খুব সহজেই বাকি টাকা আদান-প্রদান এবং আদায় রিপোর্ট তৈরি করতে পারবেন।</li>
              <li><strong>Barcode</strong> এবং <strong>QR Code</strong> প্রিন্টিং সুবিধার মাধ্যমে আপনি প্রোডাক্ট লেবেলিং করে ইনভেন্টরি ও POS উভয় ক্ষেত্রে স্ক্যানযোগ্য পরিবেশ তৈরি করতে পারবেন।</li>
              <li><strong>Coupon System</strong> এবং <strong>Gift Card System</strong>-এর মাধ্যমে আপনি কাস্টমারদের জন্য প্রচারণামূলক ডিসকাউন্ট অফার চালু করতে পারবেন এবং কাস্টমাইজড গিফট কার্ড সরবরাহ করতে পারবেন।</li>
              <li><strong>Copy Invoice URL</strong> এবং <strong>Print Chalan Paper</strong> সুবিধা আপনাকে সহজেই পেমেন্ট ডকুমেন্ট এবং চালান প্রস্তুত করতে সাহায্য করবে।</li>
              <li><strong>Order Tracking</strong> এবং <strong>Fraud Checking System</strong> থাকায় কাস্টমার অর্ডার স্ট্যাটাস দেখতে পারবেন এবং যে কোনো সন্দেহজনক অর্ডার সিস্টেম নিজে থেকে শনাক্ত করতে পারবে।</li>
            </ul>
        </li>
        <li class="mb-2"><strong>রিপোর্টিং সুবিধা (Customer Reporting Module):</strong><br>
          <p>এই সফটওয়্যারের মাধ্যমে একজন গ্রাহক তার ব্যবসার সম্পূর্ণ আর্থিক, বিক্রয়, ক্রয়, স্টক এবং মানবসম্পদ সম্পর্কিত বিশ্লেষণাত্মক ও পারফরম্যান্স ভিত্তিক রিপোর্ট রিয়েলটাইমে পেতে পারেন।</p>
          <p class="text-[12pt] font-bold mt-3 ml-6">নিচে রিপোর্টগুলোর তালিকা ও তাদের কার্যকারিতা সংক্ষেপে তুলে ধরা হলো:</p>
          <ul class="list-disc ml-8 text-[12pt]">
            <li>Overall Report: ব্যবসার সামগ্রিক কর্মদক্ষতা, আয়, ব্যয় এবং ব্যালেন্স সংক্ষেপে বিশ্লেষণ।</li>
            <li>Closing Report: প্রতিদিনের শেষে নগদ, বিক্রয় এবং খরচের সারসংক্ষেপ।</li>
            <li>Cash Flow Report: প্রতিষ্ঠানের নগদ প্রবাহ এবং বাজেট সংক্রান্ত দিকনির্দেশনা।</li>
            <li>Tax Report: ট্যাক্স ক্যালকুলেশন, পেমেন্ট ট্র্যাকিং এবং রিপোর্টিং।</li>
            <li>Best Seller Report: নির্দিষ্ট সময় অনুযায়ী সর্বাধিক বিক্রিত পণ্যের তালিকা এবং বিশ্লেষণ।</li>
            <li>Product Report: পণ্যের ইনভেন্টরি, বিক্রয়, ক্রয়, মূল্য এবং প্রফিট রিপোর্ট।</li>
            <li>Product Inventory Report: প্রতিটি পণ্যের বর্তমান মজুদ এবং স্টক হিস্ট্রি বিশ্লেষণ।</li>
            <li>Daily Sale / Monthly Sale: দৈনিক ও মাসিক বিক্রয় রিপোর্টের মাধ্যমে টার্গেটিং ও প্রবণতা বিশ্লেষণ।</li>
            <li>Daily Purchase / Monthly Purchase: ক্রয় সংক্রান্ত প্রতিদিন এবং মাসভিত্তিক বিশ্লেষণ।</li>
            <li>Sale Report: গ্রাহকভিত্তিক অথবা শাখাভিত্তিক বিক্রয় বিশ্লেষণ।</li>
            <li>Expense Report: খরচের শ্রেণি অনুযায়ী বিশ্লেষণ।</li>
            <li>Sale Report Chart: ভিজ্যুয়াল চার্টে বিক্রয়ের তুলনামূলক চিত্রায়ণ।</li>
            <li>Payment Report: কাস্টমার ও সাপ্লায়ারের পেমেন্ট ইন/আউট বিশ্লেষণ।</li>
            <li>Purchase Report: পণ্যের ক্রয়, উৎস এবং ব্যয় বিশ্লেষণ।</li>
            <li>Customer Report / Group Report: নির্দিষ্ট গ্রাহকের ব্যাবহার, বাকি, রিটার্ন এবং ফ্রিকোয়েন্সি রিপোর্ট।</li>
            <li>Customer Due Report: বাকি পরিশোধ সম্পর্কিত রিয়েলটাইম তথ্য।</li>
            <li>Supplier Report / Due Report: সরবরাহকারীর কার্যকলাপ এবং বাকি রিপোর্ট।</li>
            <li>Branch Report / Stock Chart: প্রতিটি ব্রাঞ্চের বিক্রয় ও স্টক গতি বিশ্লেষণ।</li>
            <li>Product Expiry Report: মেয়াদোত্তীর্ণ বা মেয়াদসীমার কাছাকাছি থাকা পণ্যের তালিকা।</li>
            <li>Product Quantity Alert: মজুদ কমে গেলে স্বয়ংক্রিয়ভাবে অ্যালার্ট জেনারেশন।</li>
            <li>Daily Sale Objective Report: প্রতিদিনের বিক্রয় লক্ষ্যপূরণ বিশ্লেষণ।</li>
            <li>Attendance Report: কর্মচারীর উপস্থিতি, অনুপস্থিতি এবং ছুটির তথ্য।</li>
            <li>Salary Report: বেতন প্রদান পরিসংখ্যান।</li>
            <li>User Report: ইউজার ভিত্তিক কার্যক্রম, বিক্রয় এবং ব্যবহার বিশ্লেষণ।</li>
          </ul>
        </li>
        <li class="mb-2"><strong>সেবা ও প্রযুক্তিগত সহায়তা:</strong><br>
            <ol class="list-decimal ml-6">
              <li>সেবাদাতা গ্রাহককে প্রয়োজনীয় টেকনিক্যাল সাপোর্ট, ট্রেনিং ও সফটওয়্যার রক্ষণাবেক্ষণের সেবা প্রদান করবে।</li>
              <li>গ্রাহক সাপোর্ট টিকিট সাবমিট করতে পারবেন, যা সর্বোচ্চ কর্মদিবস ২৪ ঘণ্টার মধ্যে সমাধানযোগ্য।</li>
            </ol>
        </li>
        <li class="mb-2"><strong>গ্রাহকের দায়িত্ব:</strong><br>
          <ol class="list-decimal ml-6">
            <li>সফটওয়্যার ব্যবহারের সময় কোনো অবৈধ বা অনৈতিক কার্যকলাপে লিপ্ত হলে সেবাদাতা চুক্তি বাতিল করার অধিকার সংরক্ষণ করে।</li>
            <li>গ্রাহক স্বতঃস্ফূর্তভাবে সফটওয়্যারে প্রদত্ত তথ্য এবং ট্রানজেকশন ডেটা সংরক্ষণের জন্য দায়ী থাকবেন।</li>
          </ol>
        </li>
        <li class="mb-2"><strong>সফটওয়ারের বৈশিষ্ট্যাবলী ও সীমাবদ্ধতা:</strong><br>
          <ol class="list-decimal ml-6">
              <li>সেবাদাতা কর্তৃক সরবরাহকৃত সফটওয়্যারের সকল বৈশিষ্ট্য ও মডিউলসমূহ এই চুক্তির আওতাভুক্ত থাকবে।</li>
              <li>গ্রাহকের নির্বাচিত সাবস্ক্রিপশন প্যাকেজ অনুযায়ী নির্দিষ্ট ফিচারসমূহ সফটওয়্যারে অ্যাক্টিভ থাকবে এবং ব্যবহারযোগ্য হবে।</li>
              <li>চুক্তির মেয়াদকালে গ্রাহক তার ব্যবসার চাহিদা অনুযায়ী যেকোনো সময় সাবস্ক্রিপশন প্যাকেজ পরিবর্তনের অধিকার সংরক্ষণ করেন।</li>
              <li>উক্ত সফটওয়্যারটি একটি SaaS ভিত্তিক প্ল্যাটফর্ম, যার ফিচার আপগ্রেডেশন বা নতুন মডিউল সংযোজনের পূর্ণ অধিকার শুধুমাত্র সফটওয়্যার প্রদানকারী প্রতিষ্ঠান এর উপর সংরক্ষিত, তবে এর জন্য গ্রাহকদের থেকে কোনো অতিরিক্ত চার্জ করা হবে না।</li>
              <li>যেহেতু এই SaaS ভিত্তিক সফটওয়্যারটি সর্বসাধারণ গ্রাহকদের ব্যবহারের উপযোগী করে ডিজাইন করা হয়েছে, অতএব সফটওয়্যারের স্ট্রাকচার বা মডিউল ব্যক্তিগতভাবে পরিবর্তনের সুযোগ নেই, তবে সর্বসাধারণ গ্রাহকদের প্রয়োজন অনুযায়ী কোম্পানি নিজ বিবেচনায় তা আপডেট দিতে পারে।</li>
              <li>সফটওয়্যারটিতে বিল্ট-ইন ডেটা ব্যাকআপ সিস্টেম সংযুক্ত আছে, যার মাধ্যমে গ্রাহক যেকোনো সময় তার প্রয়োজনীয় তথ্য নিজস্ব অ্যাকাউন্ট থেকে ডাউনলোড করার অধিকার রাখেন।</li>
          </ol>
        </li>
        <li class="mb-2"><strong>বাতিল ও বাতিলের প্রক্রিয়া:</strong><br>
          <ol class="list-decimal ml-6">
            <li>কোন পক্ষ যদি এই চুক্তির শর্ত ভঙ্গ করে, তবে অন্য পক্ষ ৩০ দিনের লিখিত নোটিশ প্রদানের মাধ্যমে চুক্তি বাতিল করতে পারবে।</li>
            <li>চুক্তি বাতিলের পর সফটওয়্যার ব্যবহারের অধিকার স্বয়ংক্রিয়ভাবে বিলুপ্ত হবে।</li>
          </ol>
        </li>
        <li class="mb-2"><strong> আইনি বাধ্যবাধকতা ও সালিশি ব্যবস্থা:</strong> <br>
          <ol>
            <li>এই চুক্তি বাংলাদেশের প্রচলিত আইন অনুযায়ী পরিচালিত ও বিচারাধীন হবে।</li>
            <li>কোনো বিরোধের সৃষ্টি হলে তা সালিশি পদ্ধতির মাধ্যমে নিষ্পত্তি করা হবে এবং প্রয়োজনে ঢাকা জেলা আদালত হবে সংশ্লিষ্ট বিচারব্যবস্থার ক্ষেত্র।</li>
          </ol>
        </li>
        <li class="mb-2"><strong>চূড়ান্ত বিবৃতি:</strong><br>
        <p class="text-justify">এই চুক্তিপত্রটি দুই পক্ষের পারস্পরিক সম্মতিতে প্রণীত এবং উভয় পক্ষই এর সকল ধারা, উপধারা ও দায়দায়িত্ব সম্পর্কে অবগত ও সম্মত।</p>
        </li>
      </ol>
    </div>
    <div class="mt-3">
      <p class=""><strong>গুরুত্বপূর্ণ নোট:</strong> অনুগ্রহ করে এই চুক্তির নিশ্চিতকরণ স্বরূপ প্রদত্ত চালানটি (Paid Invoice) সংগ্রহ করুন এবং এই কাগজপত্রের সাথে নিরাপদে সংরক্ষণ করুন। যদি নির্ধারিত সময়সীমার মধ্যে রেজিস্ট্রেশন ফি পরিশোধ না করা হয়, তবে এই চুক্তিপত্রটি কার্যকর চুক্তি হিসেবে বিবেচিত হবে না। সফটওয়্যার ব্যবহারকারীর অবশ্যই সম্পূর্ণ পরিশোধিত চালানসহ চুক্তিপত্রটি নিজের কাছে সংরক্ষণ করতে পারেন।</p>
    </div>
    <div class="mt-3">
      <h4 class="text-[16pt] font-bold text-center">আমাদের লক্ষ্য:</h4>
      <p class="text-justify text-[14pt]">আমাদের লক্ষ্য হলো—বাংলাদেশের প্রত্যন্ত অঞ্চল থেকে শুরু করে শহরের প্রতিটি প্রান্তে প্রযুক্তিনির্ভর ব্যবসা সম্প্রসারিত হোক। এই যাত্রায় আপনি হতে পারেন আমাদের নির্ভরযোগ্য অংশীদার ও সহযাত্রী।</p>
    </div>
    <div class="" style="margin-top: 150px">
      <div class="flex flex-col mt-2">
        <span>_______________________________</span>
        <p class="text-[14pt] mx-20">স্বাক্ষর</p>
      </div>
    </div>
  </div>

  <script>
    window.onload = function() {
      window.print();
    }
  </script>

</body>
</html>
