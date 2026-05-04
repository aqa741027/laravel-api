<?php

namespace App\Services;

use Aws\CloudFront\CloudFrontClient;

class CloudFrontService
{
    protected $client;
    protected $distributionId;

    public function __construct()
    {
        $this->client = new CloudFrontClient([
            'version' => 'latest',
            'region'  => 'us-east-1',
        ]);

        $this->distributionId = env('CLOUDFRONT_DISTRIBUTION_ID');
    }

    public function invalidate($paths = [])
    {
        return $this->client->createInvalidation([
            'DistributionId' => $this->distributionId,
            'InvalidationBatch' => [
                'Paths' => [
                    'Quantity' => count($paths),
                    'Items' => $paths,
                ],
                'CallerReference' => time(),
            ],
        ]);
    }
}
