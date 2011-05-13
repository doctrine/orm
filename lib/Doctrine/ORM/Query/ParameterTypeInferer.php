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

namespace Doctrine\ORM\Query;

use Doctrine\DBAL\Connection,
    Doctrine\DBAL\Types\Type;

/**
 * Provides an enclosed support for parameter infering.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ParameterTypeInferer
{
    /**
     * Infer type of a given value, returning a compatible constant:
     * - Type (Doctrine\DBAL\Types\Type::*) 
     * - Connection (Doctrine\DBAL\Connection::PARAM_*)
     * 
     * @param mixed $value Parameter value
     * 
     * @return mixed Parameter type constant
     */
    public static function inferType($value)
    {
        switch (true) {
            case is_integer($value):
                return Type::INTEGER;

            case ($value instanceof \DateTime):
                return Type::DATETIME;

            case is_array($value):
                $key = key($value);

                if (is_integer($value[$key])) {
                    return Connection::PARAM_INT_ARRAY;
                }

                return Connection::PARAM_STR_ARRAY;

            default:
                // Do nothing
                break;
        }

        return \PDO::PARAM_STR;
    }
}