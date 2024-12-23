<?php

namespace App\Services;

namespace App\Services;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class FpiService
{
    public function getFpiRatings()
    {
        $response = Http::get('https://www.espn.com/nfl/fpi');
        $html = $response->body();

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        // Get all tbody rows
        $rows = $xpath->query("//tr[contains(@class, 'Table__TR Table__TR--sm Table__even')]");
        $fpiData = [];

        foreach ($rows as $row) {
            $teamNode = $xpath->query(".//span[contains(@class, 'TeamLink__Name')]//a", $row)->item(0);
            if (!$teamNode) continue;

            $teamName = trim($teamNode->textContent);
            $cells = $xpath->query(".//td[contains(@class, 'Table__TD')]", $row);

            if ($cells->length >= 3) {
                // FPI value is in third cell after team name and W-L-T
                $fpiValue = trim($cells->item(2)->textContent);
                if (is_numeric($fpiValue)) {
                    $fpiData[$teamName] = (float) $fpiValue;
                }
            }
        }

        return $fpiData;
    }
}
