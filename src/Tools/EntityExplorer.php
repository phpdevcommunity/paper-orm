<?php

namespace PhpDevCommunity\PaperORM\Tools;

use PhpDevCommunity\FileSystem\Tools\FileExplorer;
use PhpDevCommunity\PaperORM\Entity\EntityInterface;

final class EntityExplorer
{

    public static function getEntities(string $dir): array
    {
        $explorer = new FileExplorer($dir);
        $files = $explorer->searchByExtension('php', true);
        $entities = [];
        foreach ($files as $file) {
            $entityClass = self::extractNamespaceAndClass($file['path']);
            if ($entityClass !== null && class_exists($entityClass) && is_subclass_of($entityClass, EntityInterface::class)) {
                $entities[$file['path']] = $entityClass;
            }
        }

        return $entities;
    }

    private static function extractNamespaceAndClass(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File not found: ' . $filePath);
        }

        $contents = file_get_contents($filePath);
        $namespace = '';
        $class = '';
        $isExtractingNamespace = false;
        $isExtractingClass = false;

        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $isExtractingNamespace = true;
            }

            if (is_array($token) && $token[0] == T_CLASS) {
                $isExtractingClass = true;
            }

            if ($isExtractingNamespace) {
                if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR,  265 /* T_NAME_QUALIFIED For PHP 8*/])) {
                    $namespace .= $token[1];
                } else if ($token === ';') {
                    $isExtractingNamespace = false;
                }
            }

            if ($isExtractingClass) {
                if (is_array($token) && $token[0] == T_STRING) {
                    $class = $token[1];
                    break;
                }
            }
        }

        if (empty($class)) {
            return null;
        }

        $fullClass = $namespace ? $namespace . '\\' . $class : $class;
        if (class_exists($fullClass) && is_subclass_of($fullClass, EntityInterface::class)) {
            return $fullClass;
        }

        return null;
    }

}
