<?php

declare(strict_types=1);

namespace Rector\Core\Application;

use Rector\ChangesReporting\Collector\AffectedFilesCollector;
use Rector\Core\PhpParser\NodeTraverser\FileWithoutNamespaceNodeTraverser;
use Rector\Core\PhpParser\NodeTraverser\RectorNodeTraverser;
use Rector\Core\PhpParser\Parser\RectorParser;
use Rector\Core\ValueObject\Application\File;
use Rector\Core\ValueObject\Configuration;
use Rector\NodeTypeResolver\NodeScopeAndMetadataDecorator;

final class FileProcessor
{
    public function __construct(
        private readonly AffectedFilesCollector $affectedFilesCollector,
        private readonly NodeScopeAndMetadataDecorator $nodeScopeAndMetadataDecorator,
        private readonly RectorParser $rectorParser,
        private readonly RectorNodeTraverser $rectorNodeTraverser,
        private readonly FileWithoutNamespaceNodeTraverser $fileWithoutNamespaceNodeTraverser,
    ) {
    }

    public function parseFileInfoToLocalCache(File $file, bool $initialParse = true): void
    {
        // store tokens by absolute path, so we don't have to print them right now
        if ($initialParse) {
            $stmtsAndTokens = $this->rectorParser->parseFileToStmtsAndTokens($file->getFilePath());
        } else {
            $file->resetStmtsAndTokens();
            $stmtsAndTokens = $this->rectorParser->parseStringToStmtsAndTokens($file->getFileContent());
        }

        $oldStmts = $stmtsAndTokens->getStmts();
        $oldTokens = $stmtsAndTokens->getTokens();

        $newStmts = $this->nodeScopeAndMetadataDecorator->decorateNodesFromFile($file, $oldStmts);
        $file->hydrateStmtsAndTokens($newStmts, $oldStmts, $oldTokens);
    }

    public function refactor(File $file, Configuration $configuration): void
    {
        $newStmts = $this->fileWithoutNamespaceNodeTraverser->traverse($file->getNewStmts());
        $newStmts = $this->rectorNodeTraverser->traverse($newStmts);

        $file->changeNewStmts($newStmts);

        $this->affectedFilesCollector->removeFromList($file);
        while (($otherTouchedFile = $this->affectedFilesCollector->getNext()) instanceof File) {
            $this->refactor($otherTouchedFile, $configuration);
        }
    }
}
