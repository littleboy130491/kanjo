<?php

namespace Database\Seeders;

use App\Models\ProposalContentDefault;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProposalContentDefaultSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        ProposalContentDefault::updateOrCreate([
            'field_key' => ProposalContentDefault::GLOBAL_FIELD_KEY,
        ], [
            'value' => [
                'en' => self::normalizeRichTextDefaults(self::defaultValueEn()),
                'id' => self::normalizeRichTextDefaults(self::defaultValueId()),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function normalizeRichTextDefaults(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::normalizeRichTextDefaults($value);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $values[$key] = self::wrapListItemTextWithParagraphs($value);
        }

        return $values;
    }

    private static function wrapListItemTextWithParagraphs(string $html): string
    {
        if (
            ! str_contains($html, '<ol')
            && ! str_contains($html, '<ul')
            && ! str_contains($html, '<li')
        ) {
            return $html;
        }

        $internalErrors = libxml_use_internal_errors(true);

        $document = new \DOMDocument('1.0', 'UTF-8');
        $encodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $document->loadHTML(
            "<!DOCTYPE html><html><body>{$encodedHtml}</body></html>",
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        $listItems = $document->getElementsByTagName('li');

        foreach ($listItems as $listItem) {
            $children = [];

            foreach ($listItem->childNodes as $childNode) {
                $children[] = $childNode;
            }

            $paragraph = null;

            foreach ($children as $childNode) {
                if (self::isBlockElement($childNode)) {
                    $paragraph = null;

                    continue;
                }

                $isLeadingWhitespaceText = $childNode->nodeType === XML_TEXT_NODE
                    && trim((string) $childNode->nodeValue) === ''
                    && $paragraph === null;

                if ($isLeadingWhitespaceText) {
                    continue;
                }

                if ($paragraph === null) {
                    $paragraph = $document->createElement('p');
                    $listItem->insertBefore($paragraph, $childNode);
                }

                $paragraph->appendChild($childNode);
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            return $html;
        }

        $normalized = '';

        foreach ($body->childNodes as $childNode) {
            $normalized .= $document->saveHTML($childNode);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $normalized;
    }

    private static function isBlockElement(\DOMNode $node): bool
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return false;
        }

        return in_array(strtolower((string) $node->nodeName), [
            'p',
            'ol',
            'ul',
            'table',
            'thead',
            'tbody',
            'tr',
            'td',
            'th',
            'blockquote',
            'pre',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'div',
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    private static function defaultValueEn(): array
    {
        return [
            'brief' => '<p>Thank you for requesting a quotation for your professional website needs. We hope to be the right partner for you.</p><p>Based on our discussion with our consulting team, here are your website requirements:</p><ol><li>Modern design that highlights the credibility and professionalism of your business/company</li><li>Responsive design optimized for 3 device sizes (desktop, mobile phone, and tablet) so the website layout always displays properly</li><li>A fast and secure website</li></ol><p>Feature details will be explained further in the features section.</p>',
            'core_services' => '<ol><li>Professional custom design up to 5 main pages</li><li>Professional custom design up to 7 main pages</li><li>Professional custom design up to 8 main pages</li><li>Professional custom design up to 10 main pages</li><li>Professional custom design up to 15 main pages</li><li>Professional custom design up to 20 main pages</li><li>Responsive design for 3 device sizes (desktop, mobile phone, and tablet)</li><li>Domain with .com extension (optional)</li><li>High performance cloud hosting</li><li>SSL certificate</li><li>Support and maintenance</li><li>Copywriting development</li><li>Licensed premium stock assets</li><li>Admin dashboard: access to manage the website and update content easily</li><li>User guide: guidance to update content independently</li><li>White label: your website is your brand, Imajiner does not leave a signature on the delivered website</li><li>SEO-friendly website</li><li>Google Analytics and Google Search Console integration: to get visitor statistics and SEO performance data</li><li>Google Tag Manager and Meta Script integration</li></ol>',
            'features' => '<ol><li>Unlimited standard pages (can be created independently)</li><li>Dual Language Feature (automatic translation via GTranslate)</li><li>Dual Language Feature (manual translation)</li><li>WhatsApp chat button</li><li>Image or video banner</li><li>Gallery to display photos and company information</li><li>Google Map on the contact page</li><li>Contact form</li><li>Social media integration (links to social media pages)</li><li>Archive Posts: article directory (blog) page to list articles with categories, sub-categories, and tags</li><li>Single Post: individual page for each article containing more detailed content</li><li>Archive Products: directory/catalog page to list products</li><li>Single Product: individual page for each product to display more detailed information (no e-commerce system)</li><li>On-page SEO: custom meta title/description settings and optimization for each page</li></ol>',
            'server' => '<ol><li>High Performance Cloud Server</li><li>High speed with 99.9% uptime</li><li>Average page load speed under 4 seconds</li><li>Capacity for up to 2,000 unique visitors per day</li><li>8 GB SSD storage</li><li>20 GB SSD storage</li><li>Unlimited bandwidth</li></ol>',
            'assets' => '<ol><li>Premium stock assets from freepik.com</li><li>Premium stock assets from elements.envato.com</li></ol>',
            'security' => '<ol><li>SSL (Secure Socket Layer) with an A rating to protect visitors and improve the website\'s reputation in search engines</li><li>Anti-malware protection against viruses and hackers</li><li>Protection against brute-force attacks and DDoS</li><li>Automatic IP blocking for suspicious activity</li><li>Daily malware scanning</li><li>Blacklist foreign hostnames attempting to log in with admin credentials</li><li>Daily data backups on the server or Google Drive</li></ol>',
            'support' => '<ol><li>Upload up to 20 images / products / posts to the website as initial content</li><li>Major Revision: major website changes (structure, flow, layout, functions, and visual design concept) during the project timeline, up to 2 revisions.</li><li>Major Revision: major website changes (structure, flow, layout, functions, and visual design concept) during the project timeline, up to 3 revisions.</li><li>Major Revision: major website changes (structure, flow, layout, functions, and visual design concept) during the project timeline, up to 4 revisions.</li><li>Major Revision: major website changes (structure, flow, layout, functions, and visual design concept) up to 30 days after website completion, up to 8 revisions.</li><li>Minor Revision: minor website changes (text, images, colors) up to 7 days after website completion</li><li>Minor Revision: minor website changes (text, images, colors) up to 30 days after website completion</li><li>Server support: support on the server side (uptime, performance, SSL configuration, server security) for 12 months</li><li>Server support: support on the server side (uptime, performance, SSL configuration, server security) for 24 months</li><li>Application (web) support: support on the web side (web performance, bugs, and troubleshooting) for 6 months</li><li>Application (web) support: support on the web side (web performance, bugs, and troubleshooting) for 12 months</li><li>Application (web) support: support on the web side (web performance, bugs, and troubleshooting) for 24 months</li><li>Help Desk support: support from the Imajiner team via WhatsApp group whenever issues arise, for 6 months</li><li>Help Desk support: support from the Imajiner team via WhatsApp group whenever issues arise, for 12 months</li><li>Update support: assistance from the Imajiner team to update website content up to 3 requests per month, non-cumulative</li><li>Update support: assistance from the Imajiner team to update website content up to 5 requests per month, non-cumulative</li><li>Update support: assistance from the Imajiner team to update website content up to 10 requests per month, non-cumulative</li><li>Update support: assistance from the Imajiner team to update website content up to 15 requests per month, non-cumulative</li><li>Update support: assistance from the Imajiner team to update website content up to 20 requests per month, non-cumulative</li><li>Technical support: support from the Imajiner team for feature changes, website structure changes, and other technical adjustments, up to 2 hours</li><li>Technical support: support from the Imajiner team for feature changes, website structure changes, and other technical adjustments, up to 4 hours</li><li>Technical support: support from the Imajiner team for feature changes, website structure changes, and other technical adjustments, up to 6 hours</li><li>Technical support: support from the Imajiner team for feature changes, website structure changes, and other technical adjustments, up to 8 hours</li></ol>',
            'additional_benefit' => '<ol><li>Premium &amp; interactive custom design</li><li>Source code provided</li><li>Domain with .com / .co.id / .id extension (optional)</li><li>Professional custom design up to 10 main pages</li><li>Professional custom design up to 15 main pages</li><li>Professional custom design up to 20 main pages</li><li>Dual Language Feature with Advanced Translation (manual input)</li><li>Live Chat contact to make it easier for website visitors to get in touch (optional)</li><li>Server with 20 GB SSD storage</li><li>Instagram Auto Feed</li><li>Multi-admin WhatsApp button</li><li>MailChimp integration for newsletter subscriptions</li><li>Archive Posts Feature: article archive (blog) page to list posts with categories, sub-categories, and tags</li><li>Single Post Feature: individual page for each article containing more detailed content</li><li>Archive Products Catalogue Feature: catalog page to list products</li><li>Single Product Feature: individual page for each product to display more detailed information (no e-commerce system)</li><li>Professional Translation Service (ENG / ID) up to 5000 words</li><li>Professional Copywriting Service (ENG / ID) up to 5000 words</li><li>Premium Stock Assets (photo, icon, video) up to 30 assets</li><li>High Performance Cloud Server: <ul><li>High speed with 99.9% uptime</li><li>Average page load speed under 4 seconds</li><li>Capacity for up to 2,000 unique visitors per day</li><li>8 GB SSD storage</li><li>Unlimited bandwidth</li></ul></li><li>High Performance Cloud Server: <ul><li>High speed with 99.9% uptime</li><li>Average page load speed under 4 seconds</li><li>Capacity for up to 2,000 unique visitors per day</li><li>20 GB SSD storage</li><li>Unlimited bandwidth</li></ul></li><li>High Performance Cloud Server: <ul><li>1vCPU, 2GB RAM</li><li>High speed with 99.9% uptime</li><li>Average page load speed under 4 seconds</li><li>Capacity for up to 2,000 unique visitors per day</li><li>50 GB SSD storage</li><li>Unlimited bandwidth</li></ul></li><li>SSL (Secure Socket Layer) with an A rating to protect visitors and improve the website\'s reputation in search engines</li><li>Daily data backups on the server or Google Drive</li><li>Major Revision: major website changes (structure, flow, layout, function, and visual design concept) up to 30 days after website completion, up to 8 revisions.</li><li>Minor Revision: minor website changes (text, images, colors) up to 30 days after website completion</li><li>Server support: support on the server side (uptime, performance, SSL configuration, server security) for 12 months</li><li>Application (web) support: support on the web side (web performance, bugs, and troubleshooting) for 12 months</li><li>Help Desk support: support from the Imajiner team via WhatsApp group whenever issues arise, for 6 months</li><li>Help Desk support: support from the Imajiner team via WhatsApp group whenever issues arise, for 12 months</li><li>Update support: assistance from the Imajiner team to update website content up to 5 requests per month, non-cumulative</li><li>Update support: assistance from the Imajiner team to update website content up to 10 requests per month, non-cumulative</li><li>Update support: assistance from the Imajiner team to update website content up to 15 requests per month, non-cumulative</li><li>Update support: assistance from the Imajiner team to update website content up to 20 requests per month, non-cumulative</li><li>Technical support: support from the Imajiner team for feature changes, website structure changes, and other technical adjustments, up to 4 hours</li><li>Technical support: support from the Imajiner team for feature changes, website structure changes, and other technical adjustments, up to 6 hours</li><li>Technical support: support from the Imajiner team for feature changes, website structure changes, and other technical adjustments, up to 8 hours</li><li>Technical support: support from the Imajiner team for feature changes, website structure changes, and other technical adjustments, up to 10 hours</li></ol>',
            'payment' => '<p>This quotation is valid for 30 days from the issue date.</p><p>If you agree to this quotation, you can make an initial payment (DP) of 50% to the following account.</p><strong><p>Bank Central Asia (BCA)<br>Account Number 002-8888-786<br>under the name of PT DIGITAL CITRA KREATIF</p></strong>',
            'terms_condition' => <<<'HTML'
<h3>1. Scope of Work</h3>
<ol>
    <li>We will design and develop the website according to the specifications agreed in this proposal.</li>
    <li>Any work outside the agreed scope, if both parties agree to proceed, will be counted as additions / add-ons and may incur additional costs.</li>
    <li>We do not guarantee or take responsibility for any results / impacts / benefits arising from the work performed.</li>
</ol>
<h3>2. Support</h3>
<ol>
    <li>Revision:
        <ul class="sub-list">
            <li>Major revision includes: changes to structure, flow, layout, function, and visual design concept.</li>
            <li>Minor revision includes: changes to text, images, color, spacing, or other visual elements on a small scale.</li>
        </ul>
    </li>
    <li>Support Types:
        <ul class="sub-list">
            <li>Server support: server uptime, performance, SSL configuration, server security.</li>
            <li>Application (Web) support: web performance, bugs and troubleshooting, web security.</li>
            <li>Help Desk support: WhatsApp group support for communication and assistance when issues occur.</li>
            <li>Update support: assistance for website content updates. Maximum 10 content items per request.</li>
            <li>Technical support: support for feature changes, website structure changes, and other technical adjustments.</li>
        </ul>
    </li>
    <li>Service Level Agreement (SLA):
        <ul class="sub-list">
            <li>Update support and technical support will be processed within a standard time of 2x24 hours from the time the request is received during business days and hours.</li>
            <li>Completion time may change or be extended depending on work complexity, number of requests, and current work queue.</li>
            <li>For work requiring a longer estimate, we will inform the client of the completion estimate before execution.</li>
        </ul>
    </li>
    <li>Response Time: We will respond to support requests within a maximum of 2 hours during business days and hours.</li>
    <li>Business Days &amp; Hours:
        <ul class="sub-list">
            <li>Monday - Friday (work and support)</li>
            <li>Saturday (support only)</li>
            <li>09:00 - 18:00 WIB</li>
        </ul>
    </li>
    <li>Support period is counted from project start.</li>
    <li>Server support only applies if using our server.</li>
</ol>
<h3>3. Website Speed (Page Load Speed)</h3>
<ol>
    <li>We guarantee average website loading speed below 4 seconds, measured using <a href="https://tools.pingdom.com/" target="_blank" rel="noopener">tools.pingdom.com</a> (location: Tokyo, Japan).</li>
    <li>If the speed already meets the standard, we are not required to perform further optimization.</li>
    <li>If the speed does not meet the standard, we may optimize by recommending simplification of website features or content.</li>
    <li>This guarantee only applies when using our provided server.</li>
</ol>
<h3>4. Search Engine Optimization (SEO)</h3>
<ol>
    <li>We ensure the website structure follows good SEO practices. The website includes tools to support SEO.</li>
    <li>We do not guarantee the website will rank at the top of Google, because rankings are influenced by many factors outside our control (for example: keyword competition, content quality, brand reputation, backlinks).</li>
    <li>The client is allowed to use SEO services from other parties.</li>
</ol>
<p>Reference: <a href="https://developers.google.com/search/docs/fundamentals/seo-starter-guide" target="_blank" rel="noopener">https://developers.google.com/search/docs/fundamentals/seo-starter-guide</a></p>
<div id="garansi">
    <h3>5. Warranty</h3>
    <ol>
        <li>The client may request a 100% refund if the outcome at the "Framework Creation" stage does not meet expectations, by filling in the form at the <a href="https://docs.google.com/document/d/1_Uho2L0Wb2nRbR5Y-qtDLMJR9SiN2xBC/edit" target="_blank" rel="noopener">following link</a>.</li>
        <li>The client receives a 100% refund warranty if we cannot fulfill the agreed scope listed in the proposal.</li>
        <li>The client receives a 100% refund warranty if project completion is delayed by more than 30 days due to our fault.</li>
        <li>The client cannot request a refund for any reason other than those stated above.</li>
    </ol>
</div>
<h3>6. Work Schedule</h3>
<ol>
    <li>We and the client are committed to following the agreed schedule.</li>
    <li>If any delay occurs, the relevant party must provide information.</li>
    <li>Delays may cause deadline adjustments.</li>
    <li>Maximum delay tolerance is 30 days for each party.</li>
    <li>The client has the right to request project cancellation and 100% refund if delay from our side exceeds the specified limit.</li>
    <li>The project is considered complete if client-side delay exceeds the specified limit and the client must complete payment.</li>
</ol>
<h3>7. Payment</h3>
<ol>
    <li>The client must pay the down payment (DP) according to the specified amount.</li>
    <li>Final payment is due no later than 30 days after project completion.</li>
    <li>We reserve the right to temporarily suspend the website if payment is overdue until settlement is completed.</li>
</ol>
<h3>8. Website Features</h3>
<ol>
    <li>Features specifically requested by the client will be described in the "Features" section of the proposal.</li>
    <li>The website is developed using the open-source WordPress CMS (wordpress.org), including plugins from the related ecosystem.</li>
    <li>If there is no specific request, features will be implemented based on our standard.</li>
    <li>Requests for more complex features are considered outside the scope of this project.</li>
</ol>
<h3>9. Additions (Add-ons)</h3>
<ol>
    <li>Major revisions outside the timeline will be considered additions and may require additional costs.</li>
    <li>Additional work will be carried out after the initial project is completed and fully paid.</li>
    <li>We are not obligated to fulfill additional feature requests.</li>
    <li>The client is allowed to independently modify, add features, and install plugins or scripts. However, we are not responsible for any issues arising from those actions.</li>
</ol>
<h3>10. Website Materials / Assets</h3>
<ol>
    <li>The client is responsible for providing website materials (photos, descriptions, videos, icons, etc.).</li>
    <li>We only provide simple photo editing (crop, resize, rotate).</li>
    <li>We can assist in sourcing icons, photos, or videos from stock asset providers. If those assets are paid, the cost will be charged to the client upon approval.</li>
</ol>
<h3>11. Legality</h3>
<ol>
    <li>We are not responsible for the legality of the website and its content.</li>
    <li>The client is fully responsible for the legality of the website and its content, and releases us from all claims.</li>
</ol>
<h3>12. Meetings</h3>
<ol>
    <li>The client gets 2 online meetings during the project.</li>
    <li>Additional online meetings may be held as needed (additional fees may apply).</li>
    <li>We do not provide meetings with third parties appointed by the client (for example: vendors / the client\'s customers) without prior agreement.</li>
    <li>Offline meetings may be available based on request and project value. Additional fees apply, depending on location and duration.</li>
</ol>
<h3>13. Annual Renewal</h3>
<ol>
    <li>Website renewal is optional.</li>
    <li>If renewed, the client receives:
        <ul class="sub-list">
            <li>Domain &amp; hosting renewal (optional).</li>
            <li>Warranty that the website functions properly.</li>
            <li>Software and license updates.</li>
            <li>Website performance and security maintenance.</li>
            <li>Support from us.</li>
        </ul>
    </li>
    <li>Renewal fees are listed in the Price List and will not increase for at least the next 3 years.</li>
    <li>If there is a fee increase, we will provide notice at least 6 months in advance.</li>
    <li>Renewal fees do not include additional features.</li>
    <li>We do not cover renewal costs for domains and hosting purchased independently by the client, unless the service is transferred to our account.</li>
    <li>To avoid losing domain and website data, ensure renewal is done on time. We are not responsible for domain or data loss caused by delayed renewal.</li>
    <li>The client can renew and manage the website independently by having the website source code and domain access.</li>
    <li>If the client wants to manage the website independently, the client is fully responsible for bugs, errors, updates, compatibility, security, and performance.</li>
    <li>For paid plugins used, if any, update warranty is provided for the next 3 years.</li>
</ol>
HTML,
            'additional_info' => '',
            'faq' => <<<'HTML'
<details><summary>What do we need to prepare before the project starts?</summary><div data-type="detailsContent"><p>Please prepare the website copy, logo, brand assets, photos, product or service information, and any reference websites you want us to review.</p></div></details>
<details><summary>Can the project timeline change?</summary><div data-type="detailsContent"><p>Yes. The timeline can change if there are additional requests, delayed materials, delayed reviews, or scope changes during the project.</p></div></details>
<details><summary>Can we request additional features after the proposal is approved?</summary><div data-type="detailsContent"><p>Additional features can be discussed after approval. If they are outside the agreed scope, they may be treated as add-ons and quoted separately.</p></div></details>
HTML,
            'marketing_program' => '<p>A 10% discount will be applied to the final payment invoice if you participate in our marketing program by leaving an honest review on our Google Business page (<a href="https://g.page/r/CSuQy97toImhEBM/review" target="_blank" rel="noopener">https://g.page/r/CSuQy97toImhEBM/review</a>) at the final payment stage before the website goes live.</p>',
            'extra_content_brief' => '',
            'add_on' => [
                [
                    'name' => 'Technical Support',
                    'description' => 'Additional technical support for feature changes, structure adjustments, or other technical adjustments beyond the provided support scope and quota.',
                    'price' => 'Rp.600.000 / hour',
                ],
                [
                    'name' => 'Online Training Session',
                    'description' => 'Online training session for system usage, website management, or other technical explanations according to client needs.',
                    'price' => 'Rp.800.000 / hour',
                ],
                [
                    'name' => 'Additional Revision',
                    'description' => 'Additional revisions requested beyond the provided revision terms and limits.',
                    'price' => 'Rp.800.000 / revision',
                ],
            ],
            'short_project_timeline' => [
                ['activity_name' => 'Material Collection', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Framework Creation', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review 1', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Review 1 Execution', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review 2', 'activity_pic' => 'Client', 'activity_days' => '1'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Final Payment', 'activity_pic' => 'Client', 'activity_days' => '1'],
            ],
            'business_project_timeline' => [
                ['activity_name' => 'Down Payment', 'activity_pic' => 'Client', 'activity_days' => '1'],
                ['activity_name' => 'Preparation and Setup', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Material Collection', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Framework Creation', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review 1', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Review 1 Execution', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review 2', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Review 2 Execution', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review 3', 'activity_pic' => 'Client', 'activity_days' => '1'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '2'],
                ['activity_name' => 'Final Payment', 'activity_pic' => 'Client', 'activity_days' => '1'],
                ['activity_name' => 'Access Provision and User Guide', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
            ],
            'prime_project_timeline' => [
                ['activity_name' => 'Down Payment', 'activity_pic' => 'Client', 'activity_days' => '1'],
                ['activity_name' => 'Project Initiation', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Material Collection', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Framework Creation', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Framework Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Design Process', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Design Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Design Revision Update', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Design Revision Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Development Process', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Revision Process', 'activity_pic' => 'All', 'activity_days' => '4'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '2'],
                ['activity_name' => 'Access Provision and User Guide', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
            ],
            'corporate_project_timeline' => [
                ['activity_name' => 'Down Payment', 'activity_pic' => 'Client', 'activity_days' => '1'],
                ['activity_name' => 'Project Initiation', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Material Collection', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Framework Creation', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Framework Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Design Process', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Design Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Design Revision Update', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Design Revision Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Development Process', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Revision Process', 'activity_pic' => 'All', 'activity_days' => '4'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '2'],
                ['activity_name' => 'Access Provision and User Guide', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
            ],
            'custom_project_timeline' => [
                ['activity_name' => 'Down Payment', 'activity_pic' => 'Client', 'activity_days' => '1'],
                ['activity_name' => 'Project Initiation', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Material Collection', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Framework Creation', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Framework Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Design Process', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Design Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Design Revision Update', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Design Revision Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Development Process', 'activity_pic' => 'Imajiner', 'activity_days' => '20'],
                ['activity_name' => 'Revision Process', 'activity_pic' => 'All', 'activity_days' => '10'],
                ['activity_name' => 'Development Revision Update', 'activity_pic' => 'Imajiner', 'activity_days' => '7'],
                ['activity_name' => 'Development Revision Review', 'activity_pic' => 'Client', 'activity_days' => '2'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '2'],
                ['activity_name' => 'Access Provision and User Guide', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function defaultValueId(): array
    {
        return [
            'brief' => '<p>Terima kasih atas permintaan penawaran untuk kebutuhan website profesional Anda. Kami berharap dapat menjadi partner yang tepat untuk Anda.</p><p>Sesuai hasil diskusi dengan tim konsultan kami, berikut adalah kebutuhan website Anda:</p><ol><li>Desain modern yang menonjolkan kredibilitas dan profesionalisme bisnis / perusahaan</li><li>Tampilan responsive menyesuaikan device (desktop, handphone, dan tablet) sehingga layout website selalu tampil secara optimal</li><li>Website cepat dan aman untuk diakses</li></ol><p>Detail fitur akan dijelaskan lebih lanjut pada bagian features.</p>',
            'core_services' => '<ol><li>Custom desain profesional sampai dengan 5 halaman utama</li><li>Custom desain profesional sampai dengan 7 halaman utama</li><li>Custom desain profesional sampai dengan 8 halaman utama</li><li>Custom desain profesional sampai dengan 10 halaman utama</li><li>Custom desain profesional sampai dengan 15 halaman utama</li><li>Custom desain profesional sampai dengan 20 halaman utama</li><li>Desain responsive menyesuaikan 3 ukuran device (desktop, handphone, dan tablet)</li><li>Domain dengan ekstensi .com (optional)</li><li>High performance cloud hosting</li><li>SSL certificate</li><li>Support dan maintenance</li><li>Pengembangan copywriting</li><li>Stock aset premium berlisensi</li><li>Admin dashboard: akses untuk mengelola website dan mengupdate konten dengan mudah</li><li>User guide: panduan untuk mengupdate konten secara mandiri</li><li>White label: website Anda adalah brand Anda, Imajiner tidak meninggalkan signature pada website yang dibuat</li><li>SEO friendly website</li><li>Integrasi Google Analytics dan Google Search Console: untuk mendapatkan data statistik pengunjung dan performansi SEO</li><li>Integrasi Google Tag Manager dan Meta Script</li></ol>',
            'features' => '<ol><li>Unlimited halaman standard (dapat dibuat secara mandiri)</li><li>Dual Language Feature (autotranslation by Gtranslate)</li><li>Dual Language Feature (manual translation)</li><li>WhatsApp Button Chat</li><li>Banner gambar atau video</li><li>Gallery untuk menampilkan foto dan informasi perusahaan</li><li>Google Map pada halaman kontak</li><li>Contact Form</li><li>Koneksi media sosial (tautan ke halaman media sosial)</li><li>Archive Posts: halaman direktori artikel (blog) untuk mencantumkan berbagai postingan artikel berikut dengan kategori, sub-kategori, tags</li><li>Single Post: halaman individu untuk masing-masing postingan artikel yang berisi detil konten lebih lanjut</li><li>Archive Products: halaman direktori / katalog untuk mencantumkan listing produk</li><li>Single Product: halaman individu untuk masing-masing produk untuk menampilkan informasi lebih lanjut (tidak ada sistem e-commerce)</li><li>On-page SEO: pengaturan / optimasi custom meta title / description untuk setiap halaman</li></ol>',
            'server' => '<ol><li>High Performance Cloud Server</li><li>Kecepatan tinggi dengan tingkat uptime 99,9%</li><li>Average speed load kurang dari 4 second</li><li>Kapasitas hingga 2000 pengunjung unik setiap harinya</li><li>Disk Space SSD 8GB</li><li>Disk Space SSD 20GB</li><li>Unlimited Bandwidth</li></ol>',
            'assets' => '<ol><li>Premium stock aset dari freepik.com</li><li>Premium stock aset dari elements.envato.com</li></ol>',
            'security' => '<ol><li>SSL (Secure Socket Layer) dengan rating A untuk keamanan pengunjung dan meningkatkan reputasi web di Search Engine</li><li>Anti-Malware sebagai proteksi terhadap virus dan hacker</li><li>Perlindungan dari Brute Force dan DDoS</li><li>Auto-banned IP dengan history yang diduga berbahaya</li><li>Daily malware scanning</li><li>Blacklist hostname asing yang berusaha login dengan kredensial admin</li><li>Backup data secara harian di server atau Google Drive</li></ol>',
            'support' => '<ol><li>Upload konten hingga 20 gambar / produk / post ke website untuk mengisi konten awal</li><li>Major Revision: perubahan pada website yang bersifat mayor (perubahan struktur, alur, tata letak, fungsi, dan konsep desain visual) selama timeline pengerjaan, dengan batas hingga 2 kali revisi.</li><li>Major Revision: perubahan pada website yang bersifat mayor (perubahan struktur, alur, tata letak, fungsi, dan konsep desain visual) selama timeline pengerjaan, dengan batas hingga 3 kali revisi.</li><li>Major Revision: perubahan pada website yang bersifat mayor (perubahan struktur, alur, tata letak, fungsi, dan konsep desain visual) selama timeline pengerjaan, dengan batas hingga 4 kali revisi.</li><li>Major Revision: perubahan pada website yang bersifat mayor (perubahan struktur, alur, tata letak, fungsi, dan konsep desain visual) hingga 30 hari setelah website selesai, dengan batas hingga 8 kali revisi.</li><li>Minor Revision: perubahan pada website yang bersifat minor (perubahan teks, gambar, warna) hingga 7 hari setelah website selesai</li><li>Minor Revision: perubahan pada website yang bersifat minor (perubahan teks, gambar, warna) hingga 30 hari setelah website selesai</li><li>Server support: support dari sisi server (server uptime, performansi, konfigurasi SSL, security server) selama 12 bulan</li><li>Server support: support dari sisi server (server uptime, performansi, konfigurasi SSL, security server) selama 24 bulan</li><li>Application (web) support: support dari sisi web (performansi web, bug dan troubleshooting) 6 bulan</li><li>Application (web) support: support dari sisi web (performansi web, bug dan troubleshooting) 12 bulan</li><li>Application (web) support: support dari sisi web (performansi web, bug dan troubleshooting) 24 bulan</li><li>Help Desk support: support dari tim Imajiner yang dapat dihubungi melalui grup WhatsApp sewaktu menemukan kendala, selama 6 bulan</li><li>Help Desk support: support dari tim Imajiner yang dapat dihubungi melalui grup WhatsApp sewaktu menemukan kendala, selama 12 bulan</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 3 kali (request) / bulan, tidak terakumulasi</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 5 kali (request) / bulan, tidak terakumulasi</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 10 kali (request) / bulan, tidak terakumulasi</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 15 kali (request) / bulan, tidak terakumulasi</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 20 kali (request) / bulan, tidak terakumulasi</li><li>Technical support: support dari tim Imajiner untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya, sebanyak 2 jam</li><li>Technical support: support dari tim Imajiner untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya, sebanyak 4 jam</li><li>Technical support: support dari tim Imajiner untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya, sebanyak 6 jam</li><li>Technical support: support dari tim Imajiner untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya, sebanyak 8 jam</li></ol>',
            'additional_benefit' => '<ol><li>Premium &amp; interactive custom design</li><li>Diberikan source code</li><li>Domain dengan ekstensi .com / .co.id / .id (opsional)</li><li>Custom desain profesional sampai dengan 10 halaman utama</li><li>Custom desain profesional sampai dengan 15 halaman utama</li><li>Custom desain profesional sampai dengan 20 halaman utama</li><li>Dual Language Feature with Advanced Translation (input manual)</li><li>Kontak via Live Chat, memudahkan pengunjung web untuk menghubungi lewat Live Chat (optional)</li><li>Server dengan Disk Space SSD 20GB</li><li>Instagram Auto Feed</li><li>Multi-admin WhatsApp Button</li><li>Integrasi MailChimp untuk subscribe newsletter</li><li>Fitur Archive Posts: halaman arsip artikel (blog) untuk mencantumkan berbagai postingan artikel berikut dengan kategori, sub-kategori, tags</li><li>Fitur Single Post: halaman individu untuk masing-masing postingan artikel yang berisi detil konten lebih lanjut</li><li>Fitur Archive Products Catalogue: halaman katalog untuk mencantumkan listing produk</li><li>Fitur Single Product: halaman individu untuk masing-masing produk untuk menampilkan informasi lebih lanjut (tidak ada sistem e-commerce)</li><li>Professional Translation Service (ENG / ID) up to 5000 words</li><li>Professional Copywriting Service (ENG / ID) up to 5000 words</li><li>Premium Stock Assets (photo, icon, video) up to 30 assets</li><li>High Performance Cloud Server: <ul><li>Kecepatan tinggi dengan tingkat uptime 99,9%</li><li>Average speed load kurang dari 4 second</li><li>Kapasitas hingga 2000 pengunjung unik setiap harinya</li><li>Disk Space SSD 8GB</li><li>Unlimited Bandwidth</li></ul></li><li>High Performance Cloud Server: <ul><li>Kecepatan tinggi dengan tingkat uptime 99,9%</li><li>Average speed load kurang dari 4 second</li><li>Kapasitas hingga 2000 pengunjung unik setiap harinya</li><li>Disk Space SSD 20GB</li><li>Unlimited Bandwidth</li></ul></li><li>High Performance Cloud Server: <ul><li>1vCPU, 2GB RAM</li><li>Kecepatan tinggi dengan tingkat uptime 99,9%</li><li>Average speed load kurang dari 4 second</li><li>Kapasitas hingga 2000 pengunjung unik setiap harinya</li><li>Disk Space SSD 50GB</li><li>Unlimited Bandwidth</li></ul></li><li>SSL (Secure Socket Layer) dengan rating A untuk keamanan pengunjung dan meningkatkan reputasi web di Search Engine</li><li>Backup data secara harian di server atau Google Drive</li><li>Major Revision: perubahan pada website yang bersifat mayor (perubahan struktur, alur, tata letak, fungsi, dan konsep desain visual) hingga 30 hari setelah website selesai, dengan batas hingga 8 kali revisi.</li><li>Minor Revision: perubahan pada website yang bersifat minor (perubahan teks, gambar, warna) hingga 30 hari setelah website selesai</li><li>Server support: support dari sisi server (server uptime, performansi, konfigurasi SSL, security server) selama 12 bulan</li><li>Application (web) support: support dari sisi web (performansi web, bug dan troubleshooting) 12 bulan</li><li>Help Desk support: support dari tim Imajiner yang dapat dihubungi melalui grup WhatsApp sewaktu menemukan kendala, selama 6 bulan</li><li>Help Desk support: support dari tim Imajiner yang dapat dihubungi melalui grup WhatsApp sewaktu menemukan kendala, selama 12 bulan</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 5 kali (request) / bulan, tidak terakumulasi</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 10 kali (request) / bulan, tidak terakumulasi</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 15 kali (request) / bulan, tidak terakumulasi</li><li>Update support: bantuan dari tim Imajiner untuk melakukan update konten pada website sebanyak 20 kali (request) / bulan, tidak terakumulasi</li><li>Technical support: support dari tim Imajiner untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya, sebanyak 4 jam</li><li>Technical support: support dari tim Imajiner untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya, sebanyak 6 jam</li><li>Technical support: support dari tim Imajiner untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya, sebanyak 8 jam</li><li>Technical support: support dari tim Imajiner untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya, sebanyak 10 jam</li></ol>',
            'payment' => '<p>Surat penawaran ini berlaku 30 hari sejak diterbitkan.</p><p>Apabila Anda menyetujui penawaran tersebut, Anda dapat melakukan pembayaran awal (DP) sebesar 50% ke rekening berikut.</p><strong><p>Bank Central Asia (BCA)<br>No. Rekening 002-8888-786<br>atas nama PT DIGITAL CITRA KREATIF</p></strong>',
            'terms_condition' => <<<'HTML'
<h3>1. Ruang Lingkup Pekerjaan</h3>
<ol>
    <li>Kami akan mendesain dan mengembangkan website sesuai dengan spesifikasi yang disepakati dalam proposal ini.</li>
    <li>Pekerjaan di luar ruang lingkup pekerjaan, apabila disepakati untuk dikerjakan oleh kedua belah pihak, akan terhitung sebagai penambahan / add-ons dan memungkinkan adanya biaya tambahan.</li>
    <li>Kami tidak menjamin ataupun bertanggung jawab terhadap hasil / dampak / benefit yang muncul dari pekerjaan yang dilakukan.</li>
</ol>
<h3>2. Support</h3>
<ol>
    <li>Revisi:
        <ul class="sub-list">
            <li>Revisi major meliputi: perubahan struktur, alur, tata letak, fungsi, dan konsep desain visual.</li>
            <li>Revisi minor meliputi: perubahan teks, gambar, warna, spacing, atau elemen visual lainnya dalam skala kecil.</li>
        </ul>
    </li>
    <li>Jenis Dukungan:
        <ul class="sub-list">
            <li>Server support: server uptime, performansi, konfigurasi SSL, security server.</li>
            <li>Application (Web) support: performansi web, bug dan troubleshooting, security web.</li>
            <li>Help Desk support: WhatsApp group support untuk berkomunikasi dan mendapatkan bantuan sewaktu menemukan kendala.</li>
            <li>Update support: bantuan untuk melakukan update konten pada website. Maksimal 10 item konten dalam setiap request.</li>
            <li>Technical support: support untuk perubahan fitur, struktur web, dan penyesuaian teknis lainnya.</li>
        </ul>
    </li>
    <li>Service Level Agreement (SLA):
        <ul class="sub-list">
            <li>Update support dan technical support akan diproses dengan waktu standar 2x24 jam terhitung sejak permintaan diterima pada hari dan jam kerja.</li>
            <li>Waktu penyelesaian dapat berubah atau diperpanjang menyesuaikan dengan tingkat kompleksitas pekerjaan, jumlah request, dan antrian pekerjaan yang sedang berjalan.</li>
            <li>Untuk pekerjaan yang membutuhkan estimasi waktu lebih panjang, kami akan menginformasikan estimasi penyelesaian kepada klien sebelum pengerjaan dilakukan.</li>
        </ul>
    </li>
    <li>Waktu Respons: Kami akan merespons permintaan bantuan maksimal dalam 2 jam pada hari dan jam kerja.</li>
    <li>Hari &amp; Jam Kerja:
        <ul class="sub-list">
            <li>Senin - Jumat (pekerjaan dan support)</li>
            <li>Sabtu (hanya support)</li>
            <li>09:00 - 18:00 WIB</li>
        </ul>
    </li>
    <li>Periode support terhitung sejak proyek dimulai.</li>
    <li>Server support hanya berlaku jika menggunakan server dari kami.</li>
</ol>
<h3>3. Kecepatan Website (Page Load Speed)</h3>
<ol>
    <li>Kami menjamin kecepatan loading website rata-rata di bawah 4 detik, diukur dengan <a href="https://tools.pingdom.com/" target="_blank" rel="noopener">tools.pingdom.com</a> (lokasi: Tokyo, Jepang).</li>
    <li>Jika kecepatan sudah memenuhi standar, kami tidak wajib melakukan optimasi lebih lanjut.</li>
    <li>Jika kecepatan belum memenuhi standar, kami mungkin melakukan optimasi dengan menyarankan penyederhanaan fitur atau konten website.</li>
    <li>Jaminan ini hanya berlaku jika menggunakan server yang kami sediakan.</li>
</ol>
<h3>4. Optimasi Mesin Pencari (SEO)</h3>
<ol>
    <li>Kami memastikan struktur website memenuhi kaidah SEO yang baik. Website dilengkapi tools untuk membantu SEO.</li>
    <li>Kami tidak menjamin website akan berada di peringkat atas Google, karena peringkat dipengaruhi banyak faktor di luar kendali kami (misalnya: persaingan kata kunci, kualitas konten, reputasi brand, backlink).</li>
    <li>Klien diperbolehkan menggunakan jasa SEO dari pihak lain.</li>
</ol>
<p>Referensi: <a href="https://developers.google.com/search/docs/fundamentals/seo-starter-guide" target="_blank" rel="noopener">https://developers.google.com/search/docs/fundamentals/seo-starter-guide</a></p>
<div id="garansi">
    <h3>5. Garansi</h3>
    <ol>
        <li>Klien diperbolehkan mengajukan refund 100% uang kembali apabila hasil pada tahap "Pembuatan Kerangka" tidak sesuai harapan, dengan mengisi form yang terdapat pada <a href="https://docs.google.com/document/d/1_Uho2L0Wb2nRbR5Y-qtDLMJR9SiN2xBC/edit" target="_blank" rel="noopener">link berikut</a>.</li>
        <li>Klien mendapatkan garansi 100% uang kembali, apabila pihak kami tidak dapat memenuhi pekerjaan sesuai yang sudah disepakati dan tercantum pada proposal.</li>
        <li>Klien mendapatkan garansi 100% uang kembali, apabila terjadi keterlambatan penyelesaian proyek melebihi 30 hari akibat kesalahan dari pihak kami.</li>
        <li>Klien tidak dapat meminta uang kembali / refund dengan alasan apa pun, selain alasan yang tertulis di atas.</li>
    </ol>
</div>
<h3>6. Jadwal Pengerjaan</h3>
<ol>
    <li>Kami dan klien berkomitmen untuk mengikuti jadwal yang disepakati.</li>
    <li>Jika ada keterlambatan, pihak terkait wajib memberikan informasi.</li>
    <li>Keterlambatan dapat menyebabkan penyesuaian deadline.</li>
    <li>Toleransi keterlambatan maksimal adalah 30 hari untuk masing-masing pihak.</li>
    <li>Klien berhak meminta pembatalan proyek dan pengembalian uang 100% apabila keterlambatan dari kami melebihi batas waktu yang ditentukan.</li>
    <li>Proyek dianggap selesai apabila keterlambatan dari klien melebihi batas waktu yang ditentukan dan klien wajib melunasi pembayaran.</li>
</ol>
<h3>7. Pembayaran</h3>
<ol>
    <li>Klien wajib membayar uang muka (DP) sesuai jumlah yang ditentukan.</li>
    <li>Pelunasan paling lambat 30 hari setelah proyek selesai.</li>
    <li>Kami berhak melakukan penutupan website sementara apabila terjadi keterlambatan pembayaran sampai pelunasan dilakukan.</li>
</ol>
<h3>8. Fitur Website</h3>
<ol>
    <li>Fitur yang klien minta secara spesifik akan dijelaskan pada bagian "Features" proposal.</li>
    <li>Website dikembangkan menggunakan CMS WordPress yang bersifat Open Source (wordpress.org) termasuk plugin dari sistem terkait.</li>
    <li>Apabila tidak ada permintaan secara spesifik, maka fitur akan dikerjakan sesuai standar kami.</li>
    <li>Permintaan fitur yang lebih kompleks dianggap berada di luar lingkup proyek ini.</li>
</ol>
<h3>9. Tambahan (Add-ons)</h3>
<ol>
    <li>Revisi major di luar timeline akan dianggap sebagai tambahan dan mungkin memerlukan biaya tambahan.</li>
    <li>Pekerjaan tambahan akan dilakukan setelah proyek awal selesai dan dilunasi.</li>
    <li>Kami tidak wajib mengerjakan permintaan tambahan fitur.</li>
    <li>Klien diperbolehkan untuk melakukan modifikasi, penambahan fitur, serta instalasi plugin atau script secara mandiri. Akan tetapi, kami tidak bertanggung jawab atas segala permasalahan yang timbul sebagai akibat dari tindakan tersebut.</li>
</ol>
<h3>10. Materi/Aset Website</h3>
<ol>
    <li>Klien berkewajiban menyediakan materi website (foto, deskripsi, video, ikon, dll.).</li>
    <li>Kami hanya menyediakan pengeditan sederhana (crop, resize, rotate) untuk foto.</li>
    <li>Kami dapat membantu mencari ikon, foto, atau video dari situs penyedia stock asset. Jika aset tersebut berbayar, biayanya akan dibebankan kepada klien dengan persetujuan.</li>
</ol>
<h3>11. Legalitas</h3>
<ol>
    <li>Kami tidak bertanggung jawab atas legalitas website dan kontennya.</li>
    <li>Klien bertanggung jawab penuh atas legalitas website dan kontennya, dan membebaskan kami dari segala tuntutan.</li>
</ol>
<h3>12. Pertemuan (Meeting)</h3>
<ol>
    <li>Klien mendapatkan 2 kali meeting online selama proyek berjalan.</li>
    <li>Meeting online tambahan dapat dilakukan sesuai kebutuhan (memungkinkan adanya biaya tambahan).</li>
    <li>Kami tidak melayani meeting dengan pihak ketiga (third-party) yang ditunjuk oleh klien (misalnya: vendor / customer dari klien), tanpa kesepakatan sebelumnya.</li>
    <li>Meeting tatap muka (offline) dapat tersedia berdasarkan permintaan dan nilai proyek. Biaya tambahan akan berlaku, tergantung lokasi dan durasi pertemuan.</li>
</ol>
<h3>13. Perpanjangan Tahunan</h3>
<ol>
    <li>Perpanjangan website bersifat opsional.</li>
    <li>Apabila melakukan perpanjangan, maka klien mendapatkan:
        <ul class="sub-list">
            <li>Perpanjangan domain &amp; hosting. (opsional)</li>
            <li>Garansi website berfungsi dengan baik.</li>
            <li>Update software dan lisensi.</li>
            <li>Maintenance performa dan keamanan website.</li>
            <li>Support dari kami.</li>
        </ul>
    </li>
    <li>Biaya perpanjangan tertera di daftar harga (Price List) dan tidak akan mengalami kenaikan selama minimal 3 tahun ke depan.</li>
    <li>Jika ada kenaikan biaya, kami akan memberikan informasi minimal 6 bulan sebelumnya.</li>
    <li>Biaya perpanjangan tidak termasuk penambahan fitur.</li>
    <li>Kami tidak menanggung biaya perpanjangan domain dan hosting yang klien beli secara mandiri, kecuali layanan tersebut ditransfer ke akun kami.</li>
    <li>Untuk menghindari kehilangan domain dan data website, pastikan perpanjangan dilakukan tepat waktu. Kami tidak bertanggung jawab atas kehilangan domain dan data website yang diakibatkan oleh keterlambatan perpanjangan.</li>
    <li>Klien dapat memperpanjang dan mengelola website secara mandiri dengan memiliki source code website dan akses domain.</li>
    <li>Apabila klien ingin mengelola website secara mandiri, maka klien bertanggung jawab sepenuhnya terhadap bug, error, update, kompatibilitas, keamanan, dan performa website.</li>
    <li>Untuk plugin berbayar yang digunakan, apabila ada, akan digaransi pembaruannya (update) selama 3 tahun ke depan.</li>
</ol>
HTML,
            'additional_info' => '',
            'faq' => <<<'HTML'
<details><summary>Apa saja yang perlu disiapkan sebelum proyek dimulai?</summary><div data-type="detailsContent"><p>Silakan siapkan copy website, logo, aset brand, foto, informasi produk atau layanan, serta referensi website yang ingin ditinjau oleh tim kami.</p></div></details>
<details><summary>Apakah timeline proyek dapat berubah?</summary><div data-type="detailsContent"><p>Ya. Timeline dapat berubah apabila ada permintaan tambahan, keterlambatan materi, keterlambatan review, atau perubahan scope selama proyek berjalan.</p></div></details>
<details><summary>Apakah kami dapat meminta fitur tambahan setelah proposal disetujui?</summary><div data-type="detailsContent"><p>Fitur tambahan dapat didiskusikan setelah proposal disetujui. Jika berada di luar scope yang disepakati, fitur tersebut dapat dianggap sebagai add-on dan dibuatkan penawaran terpisah.</p></div></details>
HTML,
            'marketing_program' => '<p>Diskon 10% akan diberikan pada invoice pelunasan apabila Anda mengikuti program marketing kami, yaitu dengan memberikan review jujur di Google Business kami (<a href="https://g.page/r/CSuQy97toImhEBM/review" target="_blank" rel="noopener">https://g.page/r/CSuQy97toImhEBM/review</a>) pada tahap pelunasan sebelum website ditayangkan (go live).</p>',
            'extra_content_brief' => '',
            'add_on' => [
                [
                    'name' => 'Technical Support',
                    'description' => 'Dukungan teknis tambahan untuk perubahan fitur, struktur, atau penyesuaian teknis lainnya yang melebihi cakupan dan kuota support yang disediakan.',
                    'price' => 'Rp.600.000 / hour',
                ],
                [
                    'name' => 'Online Training Session',
                    'description' => 'Sesi pelatihan online untuk penggunaan sistem, pengelolaan website, atau penjelasan teknis lainnya sesuai kebutuhan klien.',
                    'price' => 'Rp.800.000 / hour',
                ],
                [
                    'name' => 'Additional Revision',
                    'description' => 'Revisi tambahan yang diajukan di luar jumlah dan ketentuan revisi yang disediakan.',
                    'price' => 'Rp.800.000 / revision',
                ],
            ],
            'short_project_timeline' => [
                ['activity_name' => 'Pengumpulan Materi', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Pembuatan Kerangka', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review 1', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Pengerjaan Review 1', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review 2', 'activity_pic' => 'Klien', 'activity_days' => '1'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Pelunasan', 'activity_pic' => 'Klien', 'activity_days' => '1'],
            ],
            'business_project_timeline' => [
                ['activity_name' => 'Pembayaran DP', 'activity_pic' => 'Klien', 'activity_days' => '1'],
                ['activity_name' => 'Persiapan dan setup', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Pengumpulan Materi', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Pembuatan Kerangka', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review 1', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Pengerjaan Review 1', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review 2', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Pengerjaan Review 2', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review 3', 'activity_pic' => 'Klien', 'activity_days' => '1'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '2'],
                ['activity_name' => 'Pelunasan', 'activity_pic' => 'Klien', 'activity_days' => '1'],
                ['activity_name' => 'Pemberian Akses dan User Guide', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
            ],
            'prime_project_timeline' => [
                ['activity_name' => 'Pembayaran DP', 'activity_pic' => 'Klien', 'activity_days' => '1'],
                ['activity_name' => 'Inisiasi Project', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Pengumpulan Materi', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Pembuatan Kerangka', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review Kerangka', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Proses Desain', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review Desain', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Update Revisi Desain', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review Revisi Desain', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Proses Development', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Proses Revisi', 'activity_pic' => 'All', 'activity_days' => '4'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '2'],
                ['activity_name' => 'Pemberian Akses dan User Guide', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
            ],
            'corporate_project_timeline' => [
                ['activity_name' => 'Pembayaran DP', 'activity_pic' => 'Klien', 'activity_days' => '1'],
                ['activity_name' => 'Inisiasi Project', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Pengumpulan Materi', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Pembuatan Kerangka', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review Kerangka', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Proses Desain', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review Desain', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Update Revisi Desain', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review Revisi Desain', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Proses Development', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Proses Revisi', 'activity_pic' => 'All', 'activity_days' => '4'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '2'],
                ['activity_name' => 'Pemberian Akses dan User Guide', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
            ],
            'custom_project_timeline' => [
                ['activity_name' => 'Pembayaran DP', 'activity_pic' => 'Klien', 'activity_days' => '1'],
                ['activity_name' => 'Inisiasi Project', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
                ['activity_name' => 'Pengumpulan Materi', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Pembuatan Kerangka', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review Kerangka', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Proses Desain', 'activity_pic' => 'Imajiner', 'activity_days' => '5'],
                ['activity_name' => 'Review Desain', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Update Revisi Desain', 'activity_pic' => 'Imajiner', 'activity_days' => '3'],
                ['activity_name' => 'Review Revisi Desain', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Proses Development', 'activity_pic' => 'Imajiner', 'activity_days' => '20'],
                ['activity_name' => 'Proses Revisi', 'activity_pic' => 'All', 'activity_days' => '10'],
                ['activity_name' => 'Update Revisi Development', 'activity_pic' => 'Imajiner', 'activity_days' => '7'],
                ['activity_name' => 'Review Revisi Development', 'activity_pic' => 'Klien', 'activity_days' => '2'],
                ['activity_name' => 'Finishing', 'activity_pic' => 'Imajiner', 'activity_days' => '2'],
                ['activity_name' => 'Pemberian Akses dan User Guide', 'activity_pic' => 'Imajiner', 'activity_days' => '1'],
            ],
        ];
    }
}
