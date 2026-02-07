<?php

declare(strict_types=1);

namespace Shimmie2;

class GitPull extends Extension
{
    public const KEY = "git_pull";

    #[EventListener(priority: 1)]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("admin/git_pull"),
            SHM_SUBMIT('Pull from git'),
        );
        Ctx::$page->add_block(new Block("Git Pull", $html));
    }

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "git_pull":
                $output = $this->execGitPull();
                Log::warning("admin", $output, $output);
                $event->redirect = true;
                break;
        }
    }

    private function execGitPull(): string
    {
        $command = new CommandBuilder("git");
        $command->add_args("pull");
        return $command->execute();
    }
}
