<?php

namespace Plutuss\Traits;

use Illuminate\Support\Collection;

trait HasPropertyTrait
{

    public function __get(string $name)
    {
        $value = $this->data->get($name);

        if (is_array($value)) {
            return collect($value);
        }

        return $value;

    }

    /**
     * @return Collection|null
     */
    public function getAllInfo(): ?Collection
    {
        return $this->data;
    }

    /**
     * @return Collection|null
     */
    public function all(): ?Collection
    {
        return $this->getAllInfo();
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
