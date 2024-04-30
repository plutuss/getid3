<?php

namespace Plutuss\Traits;

use Illuminate\Support\Collection;

trait HasPropertyTrait
{

    public function __get(string $name)
    {
        return collect($this->data->get($name));
    }

    /**
     * @return Collection|null
     */
    public function getAllInfo(): ?Collection
    {
        return $this->data;
    }


}
