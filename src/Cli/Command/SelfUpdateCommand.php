<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Command;

use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Strategy\ShaStrategy;
use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\VersionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Self-update command.
 *
 * Heavily inspired from https://github.com/padraic/phar-updater.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class SelfUpdateCommand extends Command
{
    const PACKAGE_NAME = 'acmephp/acmephp';
    const FILE_NAME = 'acmephp.phar';
    const VERSION_URL = 'https://acmephp.github.io/downloads/acmephp.version';
    const PHAR_URL = 'https://acmephp.github.io/downloads/acmephp.phar';

    /**
     * @var string
     */
    protected $version;

    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Update acmephp.phar to most recent stable, pre-release or development build.')
            ->addOption(
                'dev',
                'd',
                InputOption::VALUE_NONE,
                'Update to most recent development build of Acme PHP.'
            )
            ->addOption(
                'non-dev',
                'N',
                InputOption::VALUE_NONE,
                'Update to most recent non-development (alpha/beta/stable) build of Acme PHP tagged on Github.'
            )
            ->addOption(
                'pre',
                'p',
                InputOption::VALUE_NONE,
                'Update to most recent pre-release version of Acme PHP (alpha/beta/rc) tagged on Github.'
            )
            ->addOption(
                'stable',
                's',
                InputOption::VALUE_NONE,
                'Update to most recent stable version tagged on Github.'
            )
            ->addOption(
                'rollback',
                'r',
                InputOption::VALUE_NONE,
                'Rollback to previous version of Acme PHP if available on filesystem.'
            )
            ->addOption(
                'check',
                'c',
                InputOption::VALUE_NONE,
                'Checks what updates are available across all possible stability tracks.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->version = $this->getApplication()->getVersion();
        $parser = new VersionParser();

        /*
         * Check for ancilliary options
         */
        if ($input->getOption('rollback')) {
            $this->rollback();

            return;
        }

        if ($input->getOption('check')) {
            $this->printAvailableUpdates();

            return;
        }

        /*
         * Update to any specified stability option
         */
        if ($input->getOption('dev')) {
            $this->updateToDevelopmentBuild();

            return;
        }

        if ($input->getOption('pre')) {
            $this->updateToPreReleaseBuild();

            return;
        }

        if ($input->getOption('stable')) {
            $this->updateToStableBuild();

            return;
        }

        if ($input->getOption('non-dev')) {
            $this->updateToMostRecentNonDevRemote();

            return;
        }

        /*
         * If current build is stable, only update to more recent stable
         * versions if available. User may specify otherwise using options.
         */
        if ($parser->isStable($this->version)) {
            $this->updateToStableBuild();

            return;
        }

        /*
         * By default, update to most recent remote version regardless
         * of stability.
         */
        $this->updateToMostRecentNonDevRemote();
    }

    protected function getStableUpdater()
    {
        $updater = new Updater();
        $updater->setStrategy(Updater::STRATEGY_GITHUB);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getPreReleaseUpdater()
    {
        $updater = new Updater();
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setStability(GithubStrategy::UNSTABLE);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getMostRecentNonDevUpdater()
    {
        $updater = new Updater();
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setStability(GithubStrategy::ANY);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getGithubReleasesUpdater(Updater $updater)
    {
        $updater->getStrategy()->setPackageName(self::PACKAGE_NAME);
        $updater->getStrategy()->setPharName(self::FILE_NAME);
        $updater->getStrategy()->setCurrentLocalVersion($this->version);

        return $updater;
    }

    protected function getDevelopmentUpdater()
    {
        $updater = new Updater();
        $updater->getStrategy()->setPharUrl(self::PHAR_URL);
        $updater->getStrategy()->setVersionUrl(self::VERSION_URL);

        return $updater;
    }

    protected function updateToStableBuild()
    {
        $this->update($this->getStableUpdater());
    }

    protected function updateToPreReleaseBuild()
    {
        $this->update($this->getPreReleaseUpdater());
    }

    protected function updateToMostRecentNonDevRemote()
    {
        $this->update($this->getMostRecentNonDevUpdater());
    }

    protected function updateToDevelopmentBuild()
    {
        $this->update($this->getDevelopmentUpdater());
    }

    protected function update(Updater $updater)
    {
        $this->output->writeln('Updating...'.PHP_EOL);

        try {
            $result = $updater->update();

            $newVersion = $updater->getNewVersion();
            $oldVersion = $updater->getOldVersion();
            if (40 === strlen($newVersion)) {
                $newVersion = 'dev-'.$newVersion;
            }
            if (40 === strlen($oldVersion)) {
                $oldVersion = 'dev-'.$oldVersion;
            }

            if ($result) {
                $this->output->writeln('<fg=green>Acme PHP has been updated.</fg=green>');
                $this->output->writeln(sprintf(
                    '<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
                    $newVersion
                ));
                $this->output->writeln(sprintf(
                    '<fg=green>Previous version was:</fg=green> <options=bold>%s</options=bold>.',
                    $oldVersion
                ));
            } else {
                $this->output->writeln('<fg=green>Acme PHP is currently up to date.</fg=green>');
                $this->output->writeln(sprintf(
                    '<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
                    $oldVersion
                ));
            }
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
        $this->output->write(PHP_EOL);
        $this->output->writeln('You can also select update stability using --dev, --pre (alpha/beta/rc) or --stable.');
    }

    protected function rollback()
    {
        $updater = new Updater();

        try {
            $result = $updater->rollback();
            if ($result) {
                $this->output->writeln('<fg=green>Acme PHP has been rolled back to prior version.</fg=green>');
            } else {
                $this->output->writeln('<fg=red>Rollback failed for reasons unknown.</fg=red>');
            }
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
    }

    protected function printAvailableUpdates()
    {
        $this->printCurrentLocalVersion();
        $this->printCurrentStableVersion();
        $this->printCurrentPreReleaseVersion();
        $this->printCurrentDevVersion();
        $this->output->writeln('You can select update stability using --dev, --pre or --stable when self-updating.');
    }

    protected function printCurrentLocalVersion()
    {
        $this->output->writeln(sprintf(
            'Your current local build version is: <options=bold>%s</options=bold>',
            $this->version
        ));
    }

    protected function printCurrentStableVersion()
    {
        $this->printVersion($this->getStableUpdater());
    }

    protected function printCurrentPreReleaseVersion()
    {
        $this->printVersion($this->getPreReleaseUpdater());
    }

    protected function printCurrentDevVersion()
    {
        $this->printVersion($this->getDevelopmentUpdater());
    }

    protected function printVersion(Updater $updater)
    {
        $stability = 'stable';
        if ($updater->getStrategy() instanceof ShaStrategy) {
            $stability = 'development';
        } elseif ($updater->getStrategy() instanceof GithubStrategy
            && GithubStrategy::UNSTABLE === $updater->getStrategy()->getStability()) {
            $stability = 'pre-release';
        }

        try {
            if ($updater->hasUpdate()) {
                $this->output->writeln(sprintf(
                    'The current %s build available remotely is: <options=bold>%s</options=bold>',
                    $stability,
                    $updater->getNewVersion()
                ));
            } elseif (false === $updater->getNewVersion()) {
                $this->output->writeln(sprintf('There are no %s builds available.', $stability));
            } else {
                $this->output->writeln(sprintf('You have the current %s build installed.', $stability));
            }
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
    }
}
