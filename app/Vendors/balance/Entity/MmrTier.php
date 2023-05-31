<?php

namespace App\Vendors\balance\Entity;

class MmrTier
{
    public string $name;
    public int    $min;
    public int    $max;

    public function __construct(string $name, int $min, int $max)
    {
        $this->name = $name;
        $this->min  = $min;
        $this->max  = $max;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMin()
    {
        return $this->min;
    }


    public function setMin($min): void
    {
        $this->min = $min;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function setMax(int $max): void
    {
        $this->max = $max;
    }


}