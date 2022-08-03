<?php

declare(strict_types=1);

namespace Doctrine\Performance\ChangeSet;

use Doctrine\ORM\UnitOfWork;
use Doctrine\Performance\EntityManagerFactory;
use Doctrine\Tests\Models\CMS\CmsUser;
use LogicException;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;

use function str_replace;

/**
 * @BeforeMethods({"init"})
 */
final class UnitOfWorkComputeChangesBench
{
    /** @var CmsUser[] */
    private $users;

    /** @var UnitOfWork */
    private $unitOfWork;

    public function init(): void
    {
        $this->unitOfWork = EntityManagerFactory::getEntityManager([])->getUnitOfWork();

        for ($i = 1; $i <= 100; ++$i) {
            $user           = new CmsUser();
            $user->id       = $i;
            $user->status   = 'user';
            $user->username = 'user' . $i;
            $user->name     = 'Mr.Smith-' . $i;
            $this->users[]  = $user;

            $this->unitOfWork->registerManaged(
                $user,
                ['id' => $i],
                [
                    'id'       => $user->id,
                    'status'   => $user->status,
                    'username' => $user->username,
                    'name'     => $user->name,
                    'address'  => $user->address,
                    'email'    => $user->email,
                ]
            );
        }

        $this->unitOfWork->computeChangeSets();

        if ($this->unitOfWork->getScheduledEntityUpdates()) {
            throw new LogicException('Unit of work should be clean at this stage');
        }

        foreach ($this->users as $user) {
            $user->status    = 'other';
            $user->username .= '++';
            $user->name      = str_replace('Mr.', 'Mrs.', $user->name);
        }
    }

    public function benchChangeSetComputation(): void
    {
        $this->unitOfWork->computeChangeSets();
    }
}
