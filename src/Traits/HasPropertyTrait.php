<?php

namespace Plutuss\Traits;

use Illuminate\Support\Arr;
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

    /**
     * @param string $path
     * @return mixed
     */
    public function getNestedValue(string $path): mixed
    {

        if (\Illuminate\Foundation\Application::VERSION > 11.0) {
            return fluent($this->getAllInfo())
                ->get($path);
        }

        return Arr::get($this->getAllInfo()->toArray(), $path);

    }


}
