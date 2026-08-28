<?php

namespace Database\Seeders;

use App\Models\SpkContentDefault;
use App\Support\RichTextHtmlNormalizer;
use Illuminate\Database\Seeder;

class SpkContentDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $record = SpkContentDefault::query()->firstOrNew([
            'field_key' => SpkContentDefault::GLOBAL_FIELD_KEY,
        ]);

        $value = $record->getTranslations('value') ?: [];

        foreach (['id', 'en'] as $locale) {
            $defaults = self::defaultValue($locale);
            $current = is_array($value[$locale] ?? null) ? $value[$locale] : [];

            foreach ($defaults as $field => $html) {
                $existing = $current[$field] ?? null;

                if (! is_string($existing) || trim($existing) === '') {
                    $current[$field] = $html;
                }
            }

            $value[$locale] = RichTextHtmlNormalizer::normalizeArray($current);
        }

        $record->value = $value;
        $record->save();
    }

    /**
     * @return array<string, string>
     */
    private static function defaultValue(string $locale): array
    {
        return match ($locale) {
            'en' => self::englishDefaultValue(),
            default => self::indonesianDefaultValue(),
        };
    }

    /**
     * @return array<string, string>
     */
    private static function indonesianDefaultValue(): array
    {
        return [
            'title' => self::indonesianTitleTemplate(),
            'party_identification' => self::indonesianPartyIdentificationTemplate(),
            'subject' => 'JASA PEMBUATAN WEBSITE',
            'content' => <<<'HTML'
<h3>PASAL I — RUANG LINGKUP DAN DOKUMEN ACUAN</h3>
<ol>
    <li>PIHAK PERTAMA memberikan pekerjaan berupa {{ subject }} kepada PIHAK KEDUA untuk paket <strong>{{ offer_name }}</strong>.</li>
    <li>PIHAK KEDUA menyetujui dan bersedia melaksanakan pekerjaan tersebut dengan sebaik-baiknya dan penuh tanggung jawab.</li>
    <li>Seluruh spesifikasi pekerjaan, termasuk namun tidak terbatas pada core services, fitur, aset, server, keamanan, support, add-ons/tambahan, serta ketentuan teknis lainnya, didefinisikan pada Surat Penawaran (Proposal) PIHAK KEDUA nomor <strong>{{ proposal_number }}</strong> tertanggal <strong>{{ proposal_date }}</strong>.</li>
    <li>Proposal sebagaimana dimaksud pada ayat (3) merupakan bagian yang tidak terpisahkan dan mengikat dari Perjanjian ini. Apabila terdapat perbedaan interpretasi terkait ruang lingkup pekerjaan, maka ketentuan pada Proposal yang disepakati PARA PIHAK menjadi acuan utama.</li>
    <li>Pekerjaan di luar ruang lingkup Proposal, apabila disepakati untuk dikerjakan, dianggap sebagai penambahan (add-ons) dan dapat dikenakan biaya tambahan sesuai ketentuan pada Proposal.</li>
</ol>
<h3>PASAL II — BIAYA DAN PEMBAYARAN</h3>
<p>Biaya pekerjaan yang disepakati PARA PIHAK untuk paket <strong>{{ offer_name }}</strong> adalah sebesar <strong>{{ offer_price }}</strong>.</p>
<ol>
    <li>Pembayaran uang muka (DP) sebesar 50% (lima puluh persen) dari total biaya, dilakukan setelah Perjanjian ini ditandatangani PARA PIHAK sebagai tanda awal pengerjaan.</li>
    <li>Pembayaran pelunasan sebesar 50% (lima puluh persen) sisanya dilakukan sesuai ketentuan pembayaran pada Proposal.</li>
    <li>Seluruh pembayaran dilakukan ke rekening resmi PIHAK KEDUA sebagaimana tercantum pada bagian Payment Terms Proposal nomor {{ proposal_number }}.</li>
    <li>Ketentuan perpajakan, termasuk status Non-PKP dan pemotongan PPh 23 (jika berlaku), mengacu pada Proposal.</li>
    <li>Biaya perpanjangan tahunan (jika ada) bersifat opsional dan mengacu pada daftar harga pada Proposal.</li>
    <li>Apabila terjadi keterlambatan pembayaran, PIHAK PERTAMA wajib memberitahukan PIHAK KEDUA secara tertulis beserta estimasi waktu pelunasan. PIHAK KEDUA berhak menangguhkan pekerjaan atau mengambil tindakan sesuai ketentuan pada Proposal.</li>
</ol>
<h3>PASAL III — JADWAL PEKERJAAN</h3>
<p>Pekerjaan akan dikerjakan oleh PIHAK KEDUA berdasarkan timeline paket <strong>{{ offer_name }}</strong> sebagai berikut:</p>
<p>{{ offer_timeline }}</p>
<ol>
    <li>Pekerjaan dimulai setelah PIHAK PERTAMA melakukan pembayaran uang muka (DP).</li>
    <li>PARA PIHAK berkomitmen mengikuti jadwal yang disepakati. Apabila terjadi keterlambatan, pihak terkait wajib memberitahukan secara tertulis beserta estimasi penyelesaian.</li>
    <li>Ketentuan toleransi keterlambatan, penyelesaian proyek, dan konsekuensinya mengacu pada bagian Terms &amp; Conditions Proposal, khususnya ketentuan jadwal pengerjaan.</li>
</ol>
<h3>PASAL IV — SUPPORT, REVISI, DAN LAYANAN</h3>
<ol>
    <li>Ketentuan revisi (major dan minor), jenis dukungan (server, application/web, help desk, update, technical support), SLA, jam kerja, serta masa support mengacu sepenuhnya pada bagian Support dan Terms &amp; Conditions Proposal nomor {{ proposal_number }}.</li>
    <li>Server support dari PIHAK KEDUA hanya berlaku apabila PIHAK PERTAMA menggunakan server yang disediakan oleh PIHAK KEDUA, sebagaimana dinyatakan pada Proposal.</li>
    <li>Permintaan di luar cakupan support Proposal dapat dihitung sebagai add-ons/tambahan sesuai daftar harga pada Proposal.</li>
</ol>
<h3>PASAL V — KEPEMILIKAN</h3>
<ol>
    <li>Hak milik website sepenuhnya menjadi milik PIHAK PERTAMA setelah proyek selesai dan seluruh pembayaran telah diselesaikan, sesuai ketentuan pada Proposal.</li>
    <li>Apabila PIHAK PERTAMA bermaksud mengelola website secara mandiri, PIHAK KEDUA wajib menyerahkan source code dan dokumentasi teknis yang diperlukan agar PIHAK PERTAMA dapat melakukan deploy dan pengelolaan pada infrastruktur milik PIHAK PERTAMA.</li>
</ol>
<h3>PASAL VI — MATERI, LEGALITAS, DAN PERUBAHAN</h3>
<ol>
    <li>PIHAK PERTAMA wajib menyerahkan materi website (teks, gambar, dan materi pendukung lainnya) dalam format digital sesuai kebutuhan pengerjaan.</li>
    <li>PIHAK PERTAMA bertanggung jawab penuh atas legalitas, keabsahan, dan kebenaran seluruh materi yang diserahkan, serta membebaskan PIHAK KEDUA dari segala tuntutan terkait konten website.</li>
    <li>PIHAK KEDUA akan memulai/melanjutkan pekerjaan setelah materi minimum yang diperlukan diterima, kecuali disepakati lain oleh PARA PIHAK.</li>
    <li>Perubahan fitur, struktur, halaman, atau pengembangan di luar spesifikasi Proposal wajib didiskusikan terlebih dahulu dan dapat dikenakan biaya tambahan.</li>
</ol>
<h3>PASAL VII — GARANSI, PEMBATALAN, DAN PENGAKHIRAN</h3>
<ol>
    <li>Ketentuan garansi, refund, dan pembatalan proyek mengacu pada bagian Garansi dan Terms &amp; Conditions Proposal nomor {{ proposal_number }}.</li>
    <li>Dalam hal PIHAK PERTAMA membatalkan Perjanjian di tengah pengerjaan tanpa alasan sesuai ketentuan Proposal, uang muka yang telah dibayarkan tidak dapat dikembalikan.</li>
    <li>Dalam hal PIHAK KEDUA membatalkan Perjanjian setelah penerimaan uang muka tanpa alasan sesuai ketentuan Proposal, PIHAK KEDUA wajib mengembalikan uang muka yang telah diterima.</li>
    <li>Perjanjian dapat diakhiri lebih awal apabila terjadi pelanggaran material, force majeure, atau kondisi lain sebagaimana diatur pada Proposal dan peraturan perundang-undangan yang berlaku.</li>
    <li>PARA PIHAK sepakat mengesampingkan ketentuan Pasal 1266 Kitab Undang-undang Hukum Perdata sepanjang mensyaratkan putusan pengadilan untuk pengakhiran Perjanjian.</li>
</ol>
<h3>PASAL VIII — HAL-HAL LAIN</h3>
<p>Hal-hal yang belum diatur dalam Perjanjian ini akan diselesaikan secara musyawarah oleh PARA PIHAK. Apabila diperlukan, hasil kesepakatan dapat dituangkan dalam addendum tertulis yang menjadi bagian tidak terpisahkan dari Perjanjian ini.</p>
<h3>PASAL IX — PENUTUP</h3>
<p>PARA PIHAK dengan ini menyetujui seluruh ketentuan dalam Perjanjian ini dan peraturan perundangan yang berlaku.</p>
<p>Perjanjian ini dapat ditandatangani secara elektronik. Tanda tangan digital atau elektronik memiliki kekuatan hukum yang sama dengan tanda tangan basah.</p>
<p>Demikian Perjanjian ini dibuat dan ditandatangani oleh PARA PIHAK pada tanggal sebagaimana tercantum di awal Perjanjian.</p>
HTML,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function englishDefaultValue(): array
    {
        return [
            'title' => self::englishTitleTemplate(),
            'party_identification' => self::englishPartyIdentificationTemplate(),
            'subject' => 'WEBSITE DEVELOPMENT SERVICES',
            'content' => <<<'HTML'
<h3>ARTICLE I — SCOPE OF WORK AND REFERENCE DOCUMENT</h3>
<ol>
    <li>FIRST PARTY assigns {{ subject }} to SECOND PARTY for the <strong>{{ offer_name }}</strong> package.</li>
    <li>SECOND PARTY agrees to perform the work diligently and responsibly.</li>
    <li>All work specifications, including but not limited to core services, features, assets, server, security, support, add-ons, and other technical terms, are defined in Proposal no. <strong>{{ proposal_number }}</strong> dated <strong>{{ proposal_date }}</strong>.</li>
    <li>The Proposal referred to in paragraph (3) is an integral and binding part of this Agreement. If there is any difference in interpretation regarding scope, the agreed Proposal prevails.</li>
    <li>Work outside the Proposal scope, if agreed, is treated as add-ons and may incur additional fees according to the Proposal.</li>
</ol>
<h3>ARTICLE II — FEES AND PAYMENT</h3>
<p>The agreed fee for the <strong>{{ offer_name }}</strong> package is <strong>{{ offer_price }}</strong>.</p>
<ol>
    <li>A 50% down payment is due after this Agreement is signed by both PARTIES.</li>
    <li>The remaining 50% is payable according to the payment terms in the Proposal.</li>
    <li>All payments must be made to SECOND PARTY's official bank account stated in the Payment Terms section of Proposal no. {{ proposal_number }}.</li>
    <li>Tax provisions, including Non-PKP status and PPh 23 withholding (if applicable), refer to the Proposal.</li>
    <li>Annual renewal fees, if any, are optional and refer to the price list in the Proposal.</li>
    <li>If payment is delayed, FIRST PARTY must notify SECOND PARTY in writing with an estimated settlement time. SECOND PARTY may suspend work according to the Proposal.</li>
</ol>
<h3>ARTICLE III — WORK SCHEDULE</h3>
<p>The work will be performed by SECOND PARTY based on the timeline for the <strong>{{ offer_name }}</strong> package as follows:</p>
<p>{{ offer_timeline }}</p>
<ol>
    <li>Work starts after FIRST PARTY completes the down payment.</li>
    <li>Both PARTIES commit to the agreed schedule. Any delay must be communicated in writing with an estimated completion time.</li>
    <li>Delay tolerance, project completion, and consequences refer to the Terms &amp; Conditions section of the Proposal.</li>
</ol>
<h3>ARTICLE IV — SUPPORT, REVISIONS, AND SERVICES</h3>
<ol>
    <li>Revision terms (major and minor), support types, SLA, working hours, and support period refer fully to the Support and Terms &amp; Conditions sections of Proposal no. {{ proposal_number }}.</li>
    <li>Server support applies only if FIRST PARTY uses server provided by SECOND PARTY, as stated in the Proposal.</li>
    <li>Requests outside Proposal support scope may be treated as add-ons according to the Proposal price list.</li>
</ol>
<h3>ARTICLE V — OWNERSHIP</h3>
<ol>
    <li>Website ownership belongs fully to FIRST PARTY after project completion and full payment, according to the Proposal.</li>
    <li>If FIRST PARTY wishes to manage the website independently, SECOND PARTY must deliver source code and required technical documentation.</li>
</ol>
<h3>ARTICLE VI — MATERIALS, LEGALITY, AND CHANGES</h3>
<ol>
    <li>FIRST PARTY must provide website materials (text, images, and supporting assets) in digital format as required.</li>
    <li>FIRST PARTY is fully responsible for the legality and accuracy of all submitted materials and indemnifies SECOND PARTY from related claims.</li>
    <li>SECOND PARTY starts or continues work after minimum required materials are received, unless otherwise agreed.</li>
    <li>Feature, structure, page, or development changes outside Proposal specifications require prior discussion and may incur additional fees.</li>
</ol>
<h3>ARTICLE VII — WARRANTY, CANCELLATION, AND TERMINATION</h3>
<ol>
    <li>Warranty, refund, and cancellation terms refer to the Warranty and Terms &amp; Conditions sections of Proposal no. {{ proposal_number }}.</li>
    <li>If FIRST PARTY cancels mid-project without valid grounds under the Proposal, paid down payment is non-refundable.</li>
    <li>If SECOND PARTY cancels after receiving down payment without valid grounds under the Proposal, SECOND PARTY must refund the received down payment.</li>
    <li>This Agreement may be terminated early due to material breach, force majeure, or other conditions under the Proposal and applicable law.</li>
</ol>
<h3>ARTICLE VIII — MISCELLANEOUS</h3>
<p>Matters not covered herein will be resolved by mutual discussion. If needed, results may be recorded in a written addendum forming an integral part of this Agreement.</p>
<h3>ARTICLE IX — CLOSING</h3>
<p>The PARTIES agree to all terms in this Agreement and applicable laws.</p>
<p>This Agreement may be signed electronically with the same legal effect as a wet signature.</p>
<p>This Agreement is made and signed by the PARTIES on the date stated at the beginning of this Agreement.</p>
HTML,
        ];
    }

    private static function indonesianTitleTemplate(): string
    {
        return <<<'HTML'
<p style="text-align: center;"><strong>PERJANJIAN KERJA SAMA</strong><br><strong>ANTARA</strong><br><strong>{{ client_company }}</strong><br><strong>DENGAN</strong><br><strong>{{ company_name }}</strong><br><strong>TENTANG</strong><br><strong>{{ subject }}</strong></p>
HTML;
    }

    private static function englishTitleTemplate(): string
    {
        return <<<'HTML'
<p style="text-align: center;"><strong>COOPERATION AGREEMENT</strong><br><strong>BETWEEN</strong><br><strong>{{ client_company }}</strong><br><strong>AND</strong><br><strong>{{ company_name }}</strong><br><strong>REGARDING</strong><br><strong>{{ subject }}</strong></p>
HTML;
    }

    private static function indonesianPartyIdentificationTemplate(): string
    {
        return <<<'HTML'
<p>Perjanjian Kerja sama {{ subject }}, dibuat dan ditandatangani pada tanggal {{ spk_date }} oleh dan antara:</p>
<table class="spk-party-table">
<tbody>
<tr><td>Nama</td><td>:</td><td>{{ client_pic_name }}</td></tr>
<tr><td>Jabatan</td><td>:</td><td>{{ client_pic_role }}</td></tr>
<tr><td>Perusahaan</td><td>:</td><td>{{ client_company }}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{ client_address }}</td></tr>
</tbody>
</table>
<p>Selanjutnya dalam Perjanjian ini disebut <strong>PIHAK PERTAMA</strong>.</p>
<table class="spk-party-table">
<tbody>
<tr><td>Nama</td><td>:</td><td>{{ company_pic_name }}</td></tr>
<tr><td>Jabatan</td><td>:</td><td>{{ company_pic_role }}</td></tr>
<tr><td>Perusahaan</td><td>:</td><td>{{ company_name }}</td></tr>
<tr><td>Alamat</td><td>:</td><td>{{ company_address }}</td></tr>
</tbody>
</table>
<p>Selanjutnya dalam Perjanjian ini disebut <strong>PIHAK KEDUA</strong>.</p>
<p>PIHAK PERTAMA dan PIHAK KEDUA secara bersama-sama disebut sebagai PARA PIHAK dan secara terpisah disebut PIHAK.</p>
<p>Dengan ini menerangkan bahwa PIHAK PERTAMA dan PIHAK KEDUA telah mengadakan Perjanjian sebagaimana diatur dalam pasal-pasal sebagai berikut:</p>
HTML;
    }

    private static function englishPartyIdentificationTemplate(): string
    {
        return <<<'HTML'
<p>This Cooperation Agreement for {{ subject }} is made and signed on {{ spk_date }} by and between:</p>
<table class="spk-party-table">
<tbody>
<tr><td>Name</td><td>:</td><td>{{ client_pic_name }}</td></tr>
<tr><td>Position</td><td>:</td><td>{{ client_pic_role }}</td></tr>
<tr><td>Company</td><td>:</td><td>{{ client_company }}</td></tr>
<tr><td>Address</td><td>:</td><td>{{ client_address }}</td></tr>
</tbody>
</table>
<p>Hereinafter referred to as the <strong>FIRST PARTY</strong>.</p>
<table class="spk-party-table">
<tbody>
<tr><td>Name</td><td>:</td><td>{{ company_pic_name }}</td></tr>
<tr><td>Position</td><td>:</td><td>{{ company_pic_role }}</td></tr>
<tr><td>Company</td><td>:</td><td>{{ company_name }}</td></tr>
<tr><td>Address</td><td>:</td><td>{{ company_address }}</td></tr>
</tbody>
</table>
<p>Hereinafter referred to as the <strong>SECOND PARTY</strong>.</p>
<p>FIRST PARTY and SECOND PARTY are collectively referred to as the PARTIES and individually as a PARTY.</p>
<p>The PARTIES hereby enter into this Agreement under the following articles:</p>
HTML;
    }
}
