<?php
namespace Balancepay\Balancepay\Model;

use Balancepay\Balancepay\Model\QueueFactory;
use Magento\Framework\Serialize\Serializer\Json;

class QueueProcessor
{
    public function __construct(
        QueueFactory $queueFactory,
        Json $json
    ) {
        $this->queueFactory = $queueFactory;
        $this->json = $json;
    }

    /**
     * AddToQueue
     *
     * @param array $params
     * @param string $name
     * @throws \Exception
     */
    public function addToQueue($params, $name)
    {
        $queueModel = $this->queueFactory->create();
        $queueModel->setData([
            'payload' => $this->json->serialize($params),
            'name' => $name,
            'attempts' => 1
        ]);
        $queueModel->save();
    }
}
