<?php

declare(strict_types=1);

namespace steevanb\DoctrineMappingValidator\Command;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use steevanb\DoctrineMappingValidator\{
    Mapping\MappingValidator,
    Yaml\YamlToMapping
};
use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Output\OutputInterface
};
use Symfony\Component\Finder\{
    Finder,
    SplFileInfo
};

class DoctrineMappingValidate extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:mapping:validate')
            ->addArgument('directory', InputArgument::REQUIRED, 'Directory to validate')
            ->addOption('hide-warning')
            ->setDescription('Validate Yaml mapping files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $return = 0;
        $validator = new MappingValidator();
        $validator->setNamingStrategy(new DefaultNamingStrategy());

        $directory = $input->getArgument('directory');
        $finder = (new Finder())
            ->files()
            ->name('*.orm.yml')
            ->in($input->getArgument('directory'));

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $yamlYoMapping = new YamlToMapping($file->getPathname(), $validator);
            $yamlErrors = $yamlYoMapping->validate();
            $validator->validate($yamlYoMapping->getMapping());

            if (
                count($yamlErrors) > 0
                || $validator->hasErrorsOrWarnings()
                || $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE
            ) {
                $this->outputFileName($output, $file->getPathname(), $directory);
            }

            $this
                ->outputErrors($yamlErrors, $output)
                ->outputErrors($validator->getErrors(), $output)
                ->outputWarnings($validator->getWarnings(), $input, $output);

            if (count($yamlErrors) > 0 || $validator->hasErrorsOrWarnings()) {
                $return = 1;
            }
        }

        return $return;
    }

    protected function outputFileName(OutputInterface $output, string $fileName, string $directory): self
    {
        $path = substr($fileName, strlen($directory));
        $output->writeln('<comment>' . basename($path) . '</comment> ' . dirname($path));

        return $this;
    }

    protected function outputErrors(array $errors, OutputInterface $output): self
    {
        foreach ($errors as $error) {
            $output->writeln('  <error> ERROR </error> ' . $error);
        }

        return $this;
    }

    protected function outputWarnings(array $warnings, InputInterface $input, OutputInterface $output): self
    {
        if ($input->getOption('hide-warning') === false) {
            foreach ($warnings as $warning) {
                $output->writeln('  <bg=yellow> WARNING </> ' . $warning);
            }
        }

        return $this;
    }
}
