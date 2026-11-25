<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Download a PDF the supported way: request a short-lived signed link, then fetch it. */
    protected function signedPdf(string $type, int|string $id, array $headers = [])
    {
        $url = $this->getJson("/api/v1/documents/{$type}/{$id}/link", $headers)->assertOk()->json('url');

        return $this->get($url, $headers);
    }
}
