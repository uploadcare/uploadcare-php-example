<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Uploadcare\Api;
use Uploadcare\Interfaces\Response\ProjectInfoInterface;

class ProjectInfoCommand extends Command
{
    protected static string $help = <<<TEXT
<fg=green;options=bold>Command for retrieve project info from Uploadcare API</>
TEXT;

    protected static $defaultName = 'app:project-info';

    private Api $api;

    public function __construct(Api $api, string $name = null)
    {
        parent::__construct($name);
        $this->api = $api;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Command for retrieve project info from Uploadcare API')
            ->setHelp(self::$help);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $project = $this->api->project()->getProjectInfo();
        $this->showProjectInfo($project, $io);

        return Command::SUCCESS;
    }

    protected function showProjectInfo(ProjectInfoInterface $projectInfo, SymfonyStyle $io): void
    {
        $io->table(['Property', 'Value'], [
            ['Name', $projectInfo->getName()],
            ['Public key', $projectInfo->getPubKey()],
            ['Is auto-store enabled', $projectInfo->isAutostoreEnabled() ? 'Yes' : 'No'],
            ['Collaborators', \implode(', ', \array_map(static fn ($arr) => ($arr['name'] ?? ''), $projectInfo->getCollaborators()))],
        ]);
    }
}
