<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;

/**
 * "DATE_ADD(date1, interval, unit)"
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class DateSubFunction extends DateAddFunction
{
    public $firstDateExpression = null;
    public $intervalExpression = null;
    public $unit = null;

    public function getSql(SqlWalker $sqlWalker)
    {
        $unit = strtolower($this->unit);
        if ($unit == "day") {
            return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubDaysExpression(
                $this->firstDateExpression->dispatch($sqlWalker),
                $this->intervalExpression->dispatch($sqlWalker)
            );
        } else if ($unit == "month") {
            return $sqlWalker->getConnection()->getDatabasePlatform()->getDateSubMonthExpression(
                $this->firstDateExpression->dispatch($sqlWalker),
                $this->intervalExpression->dispatch($sqlWalker)
            );
        } else {
            throw QueryException::semanticalError('DATE_SUB() only supports units of type day and month.');
        }
    }
}