<?php

namespace Database\Seeders;

use App\Models\SpkContentDefault;
use Illuminate\Database\Seeder;

class SpkContentDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $value = self::defaultValue();

        SpkContentDefault::updateOrCreate([
            'field_key' => SpkContentDefault::GLOBAL_FIELD_KEY,
        ], [
            'value' => [
                'en' => $value,
                'id' => $value,
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function defaultValue(): array
    {
        return [
            'title' => <<<'HTML'
<p style="text-align: center;"><strong>PERJANJIAN KERJASAMA</strong><br><strong>ANTARA</strong><br><strong>{{ client_company }}</strong><br><strong>DENGAN</strong><br><strong>{{ company_name }}</strong><br><strong>TENTANG</strong><br><strong>{{ subject }}</strong></p>
HTML,
            'subject' => 'JASA PEMBUATAN WEBSITE',
            'content' => <<<'HTML'
<p style="text-align: center;"><strong>Nomor SPK {{ spk_number }}</strong></p>
<p>Perjanjian Kerjasama {{ subject }}, dibuat dan ditandatangani pada tanggal {{ spk_date }} oleh dan antara:</p>
<table>
    <tbody>
        <tr><td>Nama</td><td>: {{ client_pic_name }}</td></tr>
        <tr><td>Jabatan</td><td>: {{ client_pic_role }}</td></tr>
        <tr><td>Perusahaan</td><td>: {{ client_company }}</td></tr>
        <tr><td>Alamat</td><td>: {{ client_address }}</td></tr>
    </tbody>
</table>
<p>Selanjutnya dalam Perjanjian ini disebut <strong>PIHAK PERTAMA</strong>.</p>
<table>
    <tbody>
        <tr><td>Nama</td><td>: {{ company_pic_name }}</td></tr>
        <tr><td>Jabatan</td><td>: {{ company_pic_role }}</td></tr>
        <tr><td>Perusahaan</td><td>: {{ company_name }}</td></tr>
        <tr><td>Alamat</td><td>: {{ company_address }}</td></tr>
    </tbody>
</table>
<p>Selanjutnya dalam Perjanjian ini disebut <strong>PIHAK KEDUA</strong>.</p>
<p>PIHAK PERTAMA dan PIHAK KEDUA secara bersama-sama disebut sebagai PARA PIHAK dan secara terpisah disebut PIHAK.</p>
<p>Dengan ini menerangkan bahwa PIHAK PERTAMA dan PIHAK KEDUA telah mengadakan Perjanjian sebagaimana diatur dalam pasal-pasal sebagai berikut:</p>
<h2>PASAL I<br>RUANG LINGKUP KERJASAMA</h2>
<ol>
    <li>PIHAK PERTAMA memberikan pekerjaan berupa Pembuatan Website kepada PIHAK KEDUA sesuai dengan ketentuan yang disepakati dalam Perjanjian ini.</li>
    <li>PIHAK KEDUA menyetujui dan bersedia melaksanakan pekerjaan yang telah diberikan oleh PIHAK PERTAMA dengan sebaik-baiknya dan penuh tanggung jawab sesuai dengan Perjanjian ini, yang mengacu kepada penawaran resmi yang diberikan.</li>
    <li>PIHAK KEDUA akan memberikan Major Revision kepada PIHAK PERTAMA sebanyak 4 (empat) kali sesuai dengan timeline pengerjaan proyek. Revisi major yang diminta di luar timeline ataupun di luar kesepakatan akan dikenakan biaya tambahan.</li>
    <li>Major Revision meliputi perubahan struktur, alur, tata letak, fungsi, dan konsep desain visual.</li>
    <li>PIHAK PERTAMA mendapatkan garansi gratis Minor Revision sampai dengan 14 (empat belas) hari setelah pekerjaan selesai. Revisi minor meliputi perubahan teks, gambar, warna, spacing, atau elemen visual lainnya dalam skala kecil.</li>
    <li>PIHAK KEDUA akan memberikan Application (web) support selama 12 bulan, terhitung sejak proyek dimulai.</li>
    <li>PIHAK KEDUA akan memberikan Help Desk support melalui WhatsApp group selama 12 bulan, terhitung sejak proyek dimulai.</li>
    <li>PIHAK KEDUA akan memberikan Update support sebanyak 5 kali request per bulan, tidak terakumulasi, selama 12 bulan.</li>
    <li>PIHAK KEDUA akan memberikan Technical Support sebanyak 4 jam dalam 12 bulan.</li>
</ol>
<p>Perjanjian ini berlaku 1 (satu) tahun terhitung mulai sejak perjanjian ini ditandatangani oleh PARA PIHAK.</p>
<h2>PASAL II<br>BIAYA DAN PEMBAYARAN</h2>
<p>Biaya pekerjaan yang telah disepakati PARA PIHAK yaitu dikenakan kepada PIHAK PERTAMA sebesar {{ offer_price }} untuk {{ offer_name }}.</p>
<ol>
    <li>Pembayaran uang muka sebesar 50% dari total biaya tersebut di atas, setelah Perjanjian ini ditandatangani oleh PARA PIHAK, sebagai tanda awal pengerjaan.</li>
    <li>Pembayaran tahap pelunasan sebesar 50% dari total biaya tersebut dilakukan pada saat memasuki proses finishing.</li>
    <li>Jika pembayaran tahap pelunasan melebihi 7 (tujuh) hari kerja dari diterimanya invoice pelunasan, maka PIHAK PERTAMA wajib memberikan informasi kepada PIHAK KEDUA mengenai keterlambatannya beserta estimasi waktu yang dibutuhkan.</li>
</ol>
<h2>PASAL III<br>JADWAL PEKERJAAN</h2>
<p>Pekerjaan pembuatan Website akan dikerjakan oleh PIHAK KEDUA dalam estimasi waktu 35 (tiga puluh lima) hari kerja berdasarkan timeline dengan pengaturan sebagai berikut:</p>
<table>
    <thead><tr><th>Kegiatan</th><th>PIC</th><th>Jumlah Hari</th></tr></thead>
    <tbody>
        <tr><td>Pembayaran DP</td><td>Klien</td><td>1 hari</td></tr>
        <tr><td>Inisiasi Project</td><td>Imajiner</td><td>1 hari</td></tr>
        <tr><td>Pengumpulan Materi</td><td>Klien</td><td>2 hari</td></tr>
        <tr><td>Pembuatan Kerangka</td><td>Imajiner</td><td>5 hari</td></tr>
        <tr><td>Review Kerangka</td><td>Klien</td><td>2 hari</td></tr>
        <tr><td>Proses Desain</td><td>Imajiner</td><td>5 hari</td></tr>
        <tr><td>Review Desain</td><td>Klien</td><td>2 hari</td></tr>
        <tr><td>Update Revisi Desain</td><td>Imajiner</td><td>3 hari</td></tr>
        <tr><td>Review Revisi Desain</td><td>Klien</td><td>2 hari</td></tr>
        <tr><td>Proses Development</td><td>Imajiner</td><td>5 hari</td></tr>
        <tr><td>Proses Revisi</td><td>All</td><td>4 hari</td></tr>
        <tr><td>BAST dan Finishing</td><td>Imajiner</td><td>2 hari</td></tr>
        <tr><td>Pemberian Akses dan User Guide</td><td>Imajiner</td><td>1 hari</td></tr>
        <tr><td><strong>Total Hari Kerja</strong></td><td></td><td><strong>35 hari</strong></td></tr>
    </tbody>
</table>
<p>Pekerjaan mulai dilakukan oleh PIHAK KEDUA setelah PIHAK PERTAMA melakukan pembayaran uang muka (DP).</p>
<p>PARA PIHAK akan bekerja sama agar pengerjaan website sesuai dengan timeline. Apabila ada keterlambatan yang diakibatkan oleh salah satu pihak, maka pihak tersebut wajib menginfokan secara tertulis alasan keterlambatan beserta estimasi waktu pengerjaan yang disepakati PARA PIHAK.</p>
<h2>PASAL IV<br>PELAYANAN &amp; SCOPE PEKERJAAN</h2>
<p>PIHAK PERTAMA akan mendapatkan layanan pembuatan website dengan jumlah halaman utama sesuai kesepakatan PARA PIHAK.</p>
<p>PIHAK PERTAMA akan mendapatkan pelayanan dari PIHAK KEDUA sesuai dengan spesifikasi detail yang tertulis pada surat penawaran dari PIHAK KEDUA nomor {{ proposal_number }} tertanggal {{ proposal_date }}.</p>
<p>Apabila terdapat penambahan halaman, perubahan struktur halaman, atau pengembangan fitur di luar spesifikasi yang telah disepakati, maka perubahan tersebut akan didiskusikan terlebih dahulu oleh PARA PIHAK dan memungkinkan adanya biaya tambahan tergantung dengan kompleksitas pekerjaan.</p>
<h2>PASAL V<br>KEPEMILIKAN</h2>
<p>Apabila PIHAK PERTAMA bermaksud untuk mengelola website secara mandiri di masa mendatang, PIHAK KEDUA selaku penyedia jasa berkewajiban untuk menyerahkan seluruh source code beserta dokumentasi teknis yang diperlukan, sehingga PIHAK PERTAMA dapat melakukan deploy dan pengelolaan pada hosting internal milik PIHAK PERTAMA.</p>
<h2>PASAL VI<br>PERSYARATAN DAN KONDISI</h2>
<ol>
    <li>PIHAK PERTAMA menyerahkan gambar yang ingin dipakai kepada PIHAK KEDUA dalam format digital yang disediakan oleh PIHAK PERTAMA sendiri, atau PIHAK KEDUA dapat melakukan pencarian gambar berbayar sesuai kebutuhan pengerjaan.</li>
    <li>PIHAK PERTAMA memberikan materi teks dalam bentuk soft copy.</li>
    <li>PIHAK KEDUA akan mengerjakan pembuatan website setelah PIHAK PERTAMA menyerahkan materi yang diperlukan.</li>
    <li>PIHAK PERTAMA menyetujui setiap materi yang diserahkan kepada PIHAK KEDUA adalah untuk tujuan publikasi dan tidak mengandung hal-hal yang mengacu pada pornografi, pelanggaran nilai kesusilaan, kebebasan pribadi, virus komputer, penggunaan yang melecehkan atau membahayakan, dan aktivitas ilegal.</li>
    <li>PIHAK KEDUA tidak bertanggung jawab atas keabsahan materi yang diberikan dan akibat yang ditimbulkan dari pemasangan materi tersebut di website.</li>
    <li>PIHAK KEDUA akan mengerjakan proyek sesuai dengan spesifikasi yang sudah disetujui. Jika ada fitur atau pengerjaan di luar scope yang sudah disetujui, PIHAK KEDUA dapat memberikan solusi alternatif sebagai servis tambahan atau menolak request PIHAK PERTAMA.</li>
</ol>
<h2>PASAL VII<br>PEMBATALAN PERJANJIAN</h2>
<p>Perjanjian ini berlaku 1 (satu) tahun terhitung dari tanggal penandatanganan perjanjian oleh kedua belah pihak dan akan berakhir secara otomatis dalam hal telah berakhirnya jangka waktu perjanjian dan para pihak tidak menyepakati perpanjangan jangka waktu.</p>
<p>Sebelum berakhirnya jangka waktu sebagaimana dimaksud dalam Perjanjian ini, Perjanjian dapat diakhiri apabila salah satu pihak melakukan pelanggaran, terdapat ketentuan perundang-undangan atau kebijakan pemerintah yang tidak memungkinkan berlangsungnya kerja sama, izin usaha salah satu pihak dicabut, likuidasi salah satu pihak, atau salah satu pihak dinyatakan pailit berdasarkan keputusan berkekuatan hukum tetap.</p>
<p>Pihak yang mengalami kerugian dapat mengakhiri Perjanjian ini dengan menyampaikan pemberitahuan tertulis terlebih dahulu kepada pihak lainnya selambat-lambatnya 7 (tujuh) hari kalender.</p>
<p>Dalam hal PIHAK PERTAMA membatalkan Perjanjian di tengah pengerjaan, uang muka yang sudah dibayarkan tidak dapat dikembalikan dan sepenuhnya menjadi milik PIHAK KEDUA. Dalam hal PIHAK KEDUA melakukan pembatalan setelah pembayaran uang muka, PIHAK KEDUA wajib mengembalikan seutuhnya uang muka yang sudah dibayarkan.</p>
<p>PARA PIHAK sepakat untuk mengesampingkan berlakunya ketentuan Pasal 1266 Kitab Undang-undang Hukum Perdata sepanjang ketentuan yang mensyaratkan adanya putusan atau penetapan pengadilan untuk mengakhiri suatu Perjanjian.</p>
<h2>PASAL VIII<br>HAL-HAL LAIN</h2>
<p>Hal-hal yang tidak atau belum cukup diatur dalam kontrak Perjanjian ini akan diputuskan atas dasar musyawarah oleh PARA PIHAK, yang hasilnya akan dituangkan dalam Perjanjian terpisah dan menjadi bagian yang tidak dapat dipisahkan dari Perjanjian ini.</p>
<h2>PASAL IX<br>PENUTUP</h2>
<p>PARA PIHAK dengan ini menyetujui dan menyatakan bersedia seluruh ketentuan dalam Perjanjian ini dan peraturan perundangan yang berlaku.</p>
<p>Perjanjian ini dan dokumen-dokumen lain yang akan diberikan terkait dengannya dapat ditandatangani secara elektronik, sehingga tanda tangan digital atau elektronik adalah sama sebagaimana tanda tangan yang dibuat dengan tulisan tangan untuk tujuan validitas, keberlakuan, dan dapat diterima.</p>
<p>Demikianlah, PARA PIHAK dari Perjanjian ini telah menandatangani Perjanjian ini oleh para wakil mereka yang berwenang. Perjanjian ini dibuat dan ditandatangani pada hari, tanggal, bulan, dan tahun sebagaimana tercantum di awal Perjanjian.</p>
HTML,
        ];
    }
}
