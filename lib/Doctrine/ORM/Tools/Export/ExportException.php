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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Export;

use Doctrine\ORM\ORMException;

use function sprintf;

/**
 * @deprecated 2.7 This class is being removed from the ORM and won't have any replacement
 */
class ExportException extends ORMException
{
    /**
     * @param string $type
     *
     * @return ExportException
     */
    public static function invalidExporterDriverType($type)
    {
        return new self(sprintf(
            "The specified export driver '%s' does not exist",
            $type
        ));
    }

    /**
     * @param string $type
     *
     * @return ExportException
     */
    public static function invalidMappingDriverType($type)
    {
        return new self(sprintf(
            "The mapping driver '%s' does not exist",
            $type
        ));
    }

    /**
     * @param string $file
     *
     * @return ExportException
     */
    public static function attemptOverwriteExistingFile($file)
    {
        return new self("Attempting to overwrite an existing file '" . $file . "'.");
    }
}
