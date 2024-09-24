<?php

namespace Plutuss\Traits;

use Illuminate\Support\Collection;

trait HasPropertyTrait
{

    public function __get(string $name)
    {
        if (is_array($this->data->get($name))) {
            return collect($this->data->get($name));
        }

        return $this->data->get($name);

    }

    /**
     * @return Collection|null
     */
    public function getAllInfo(): ?Collection
    {
        return $this->data;
    }

    /**
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function getNestedValue(string $path, mixed $default = null): mixed
    {
        return data_get($this->getAllInfo(), $path, $default);
    }


}
