<?php

declare(strict_types=1);

namespace Pantono\Database\Traits;

use ReflectionClass;
use Pantono\Utilities\ReflectionUtilities;
use Pantono\Contracts\Application\Proxy\ProxyInterface;

trait SavableModel
{
    /**
     * @return array<string, mixed>
     */
    public function getAllData(): array
    {
        $class = new ReflectionClass($this);
        $properties = $class->getProperties();
        if (in_array(ProxyInterface::class, $class->getInterfaceNames())) {
            //If this is a proxy, use the parent class
            $properties = $class->getParentClass()->getProperties();
        }
        $data = [];
        foreach ($properties as $property) {
            $info = ReflectionUtilities::parseAttributesIntoConfig($property);
            $use = $this->parseUseStatements($class->getFileName());
            $noSave = $info['no_save'];
            $filter = $info['filter'];
            $columnName = $info['field_name'];
            $type = $info['type'];
            if ($noSave) {
                continue;
            }
            $getter = 'get' . ucfirst($property->getName());
            if ($property->getName() === 'id') {
                continue;
            }
            if (str_starts_with($type, '\\')) {
                $useClass = substr($type, 1);
                if (str_ends_with($useClass, '[]')) {
                    continue;
                }
                $type = $use[$useClass] ?? $useClass;
            }
            if (str_starts_with($type, '?')) {
                $type = substr($type, 1);
            }

            if ($type === 'bool' || $type === 'boolean') {
                $getter = 'is' . ucfirst($property->getName());
            }

            if (method_exists($this, $getter)) {
                $value = $this->$getter();

                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format($info['format'] ?: 'Y-m-d H:i:s');
                }
                if (is_object($value) && method_exists($value, 'getId')) {
                    $value = $value->getId();
                }
                if ($type === 'float') {
                    if ($value < 0.1 && $value > 0) {
                        $value = (float)number_format($value, 2);
                    }
                }
                if (is_array($value)) {
                    if ($filter === 'json_decode') {
                        $value = json_encode($value);
                    } else {
                        continue;
                    }
                }
                if ($type === 'bool' || $type === 'boolean') {
                    $value = $value ? 1 : 0;
                }

                $data[$columnName] = $value;
            }
        }

        return $data;
    }

    /**
     * @return array<mixed>
     */
    private function parseUseStatements(string $filename): array
    {
        $tokens = token_get_all(file_get_contents($filename));
        $inUse = false;
        $currentValue = [];
        $useStatements = [];
        $inAs = false;
        $statementName = null;
        $inClass = false;
        foreach ($tokens as $token) {
            if (is_array($token) && $inClass === false) {
                $code = $token[0];
                $value = $token[1];
                if ($code === T_AS) {
                    $inAs = true;
                }
                if ($inUse === true && $code === T_STRING && !$inAs) {
                    $currentValue[] = $value;
                    $statementName = $value;
                }
                if ($inAs && $code === T_STRING) {
                    $statementName = $value;
                }
                if ($code === T_USE) {
                    $inUse = true;
                }
            }
            if ((int)$token === T_CLASS) {
                $inClass = true;
            }
            if (
                $token === ';
                    ' && $inUse === true
            ) {
                $useStatements[$statementName] = implode('\\', $currentValue);
                $currentValue = [];
                $inAs = false;
                $statementName = null;
                $inUse = false;
            }
        }

        return $useStatements;
    }
}
