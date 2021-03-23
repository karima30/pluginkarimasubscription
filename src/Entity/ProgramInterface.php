<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface ProgramInterface extends ResourceInterface, TimestampableInterface
{
    public function countProgramDetails(): int;

    public function getProgramDetails();

    public function setProgramDetails($programtDetails);

    public function addProgramDetail($programtDetail);

    public function removeProgramDetail($programtDetail);


    /**
     * @return string
     */
    public function getPeriodicity();

    /**
     * @param string $periodicity
     */
    public function setPeriodicity($periodicity);

    /**
     * @return int
     */
    public function getMinimumsubscription();

    /**
     * @param int $minimumsubscription
     */
    public function setMinimumsubscription($minimumsubscription);

    /**
     * @return int
     */
    public function getMaximumsubscription();

    /**
     * @param int $maximumsubscription
     */
    public function setMaximumsubscription(int $maximumsubscription);
}
