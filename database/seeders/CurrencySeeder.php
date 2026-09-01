<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED'],
            ['code' => 'AFN', 'name' => 'Afghan Afghani', 'symbol' => '؋'],
            ['code' => 'ALL', 'name' => 'Albanian Lek', 'symbol' => 'Lek'],
            ['code' => 'AMD', 'name' => 'Armenian Dram', 'symbol' => '֏'],
            ['code' => 'ANG', 'name' => 'Netherlands Antillean Guilder', 'symbol' => 'ƒ'],
            ['code' => 'AOA', 'name' => 'Angolan Kwanza', 'symbol' => 'Kz'],
            ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => '$'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => '$'],
            ['code' => 'AWG', 'name' => 'Aruban Florin', 'symbol' => 'ƒ'],
            ['code' => 'AZN', 'name' => 'Azerbaijani Manat', 'symbol' => '₼'],
            ['code' => 'BAM', 'name' => 'Bosnia and Herzegovina Convertible Mark', 'symbol' => 'KM'],
            ['code' => 'BBD', 'name' => 'Barbadian Dollar', 'symbol' => '$'],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳'],
            ['code' => 'BGN', 'name' => 'Bulgarian Lev', 'symbol' => 'лв'],
            ['code' => 'BHD', 'name' => 'Bahraini Dinar', 'symbol' => '.د.ب'],
            ['code' => 'BIF', 'name' => 'Burundian Franc', 'symbol' => 'FBu'],
            ['code' => 'BND', 'name' => 'Brunei Dollar', 'symbol' => '$'],
            ['code' => 'BOB', 'name' => 'Bolivian Boliviano', 'symbol' => 'Bs.'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            ['code' => 'BSD', 'name' => 'Bahamian Dollar', 'symbol' => '$'],
            ['code' => 'BWP', 'name' => 'Botswana Pula', 'symbol' => 'P'],
            ['code' => 'BYN', 'name' => 'Belarusian Ruble', 'symbol' => 'Br'],
            ['code' => 'BZD', 'name' => 'Belize Dollar', 'symbol' => 'BZ$'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => '$'],
            ['code' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF'],
            ['code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => '$'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'symbol' => '$'],
            ['code' => 'CRC', 'name' => 'Costa Rican Colón', 'symbol' => '₡'],
            ['code' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'Kč'],
            ['code' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr'],
            ['code' => 'DOP', 'name' => 'Dominican Peso', 'symbol' => 'RD$'],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => '£'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'GEL', 'name' => 'Georgian Lari', 'symbol' => '₾'],
            ['code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => '₵'],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => '$'],
            ['code' => 'HRK', 'name' => 'Croatian Kuna', 'symbol' => 'kn'],
            ['code' => 'HUF', 'name' => 'Hungarian Forint', 'symbol' => 'Ft'],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
            ['code' => 'ILS', 'name' => 'Israeli New Shekel', 'symbol' => '₪'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹'],
            ['code' => 'ISK', 'name' => 'Icelandic Króna', 'symbol' => 'kr'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
            ['code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh'],
            ['code' => 'KGS', 'name' => 'Kyrgyzstani Som', 'symbol' => 'с'],
            ['code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩'],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KD'],
            ['code' => 'KZT', 'name' => 'Kazakhstani Tenge', 'symbol' => '₸'],
            ['code' => 'LBP', 'name' => 'Lebanese Pound', 'symbol' => '£'],
            ['code' => 'LKR', 'name' => 'Sri Lankan Rupee', 'symbol' => 'Rs'],
            ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'DH'],
            ['code' => 'MMK', 'name' => 'Myanmar Kyat', 'symbol' => 'Ks'],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$'],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
            ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦'],
            ['code' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr'],
            ['code' => 'NPR', 'name' => 'Nepalese Rupee', 'symbol' => 'Rs'],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => '$'],
            ['code' => 'OMR', 'name' => 'Omani Rial', 'symbol' => 'OMR'],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱'],
            ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'symbol' => 'Rs'],
            ['code' => 'PLN', 'name' => 'Polish Złoty', 'symbol' => 'zł'],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'QR'],
            ['code' => 'RON', 'name' => 'Romanian Leu', 'symbol' => 'lei'],
            ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽'],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SAR'],
            ['code' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr'],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => '$'],
            ['code' => 'THB', 'name' => 'Thai Baht', 'symbol' => '฿'],
            ['code' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => '₺'],
            ['code' => 'TWD', 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$'],
            ['code' => 'UAH', 'name' => 'Ukrainian Hryvnia', 'symbol' => '₴'],
            ['code' => 'USD', 'name' => 'United States Dollar', 'symbol' => '$'],
            ['code' => 'UYU', 'name' => 'Uruguayan Peso', 'symbol' => '$U'],
            ['code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫'],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R'],
        ];

        DB::table('currencies')->upsert($currencies, ['code']);
    }
}
