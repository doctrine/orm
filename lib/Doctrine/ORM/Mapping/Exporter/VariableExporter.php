<?php

declare(strict_types = 1);

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

namespace Doctrine\ORM\Mapping\Builder;

class VariableExporter implements Exporter
{
    const INDENTATION =  '    ';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }

        $indentation  = str_repeat(self::INDENTATION, $indentationLevel);
        $longestKey   = array_reduce(array_keys($value), function ($k, $v) {
            return (string) (strlen((string) $k) > strlen((string) $v) ? $k : $v);
        });
        $maxKeyLength = strlen($longestKey) + (is_numeric($longestKey) ? 0 : 2);

        $lines = [];

        $lines[] = $indentation . '[';

        foreach ($value as $entryKey => $entryValue) {
            $lines[] = sprintf('%s%s => %s,',
                $indentation . self::INDENTATION,
                str_pad(var_export($entryKey, true), $maxKeyLength),
                ltrim($this->export($entryValue, $indentationLevel + 1))
            );
        }

        $lines[] = $indentation . ']';

        return implode(PHP_EOL, $lines);
    }
}