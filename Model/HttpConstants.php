<?php

namespace Balancepay\Balancepay\Model;

class HttpConstants
{
    public const HEADER_CONTENT_TYPE = 'Content-Type';
    public const HEADER_BALANCE_X_API_KEY = 'x-api-key';
    public const HEADER_BALANCE_SOURCE = 'source';

    public const CONTENT_TYPE_APPLICATION_JSON = 'application/json';
    public const BALANCE_SOURCE_MAGENTO = 'magento';
    public const HTTP_USER_AGENT = 'User-Agent';
    public const HTTP_HARDCODED_USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Magento/20100101 Firefox/15.0.1';
}
