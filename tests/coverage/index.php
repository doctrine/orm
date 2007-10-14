<?php
/*
 *  $Id: Doctrine.php 1976 2007-07-11 22:03:47Z zYne $
 *
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
 * <http://www.phpdoctrine.com>.
 */

require_once dirname(__FILE__) . '/../../lib/Doctrine.php';
require_once dirname(__FILE__) . '/../DoctrineTest/Coverage.php';
$reporter = new DoctrineTest_Coverage();
$svn_info = explode(" ", exec("svn info | grep Revision"));
$revision = $svn_info[1];


?>
<html>
    <head>
        <style type="text/css">
            .covered{ background: green;}
            .normal{ background: white;}
            .red{ background: red;}
            .orange{ background: #f90;}
       </style>
</head>
<body>
    <h1>Coverage report for Doctrine</h1>
    <p>Report generated against revision <?php echo $reporter->getRevision(); ?> current HEAD revision is <?php echo $revision ?>.</p>
    <p>Default mode shows results sorted by percentage with highest first. Customize the ordering with the following GET parameters:<br /> <ul><li>order = covered|total|maybe|notcovered|percentage</li><li>flip=true</li></ul></p>
    <table>
        <tr>
            <th></th>
            <th>Percentage</th>
            <th>Total</th>
            <th>Covered</th>
            <th>Maybe</th>
            <th>Not Covered</th>
            <th></th>
        </tr>
        <?php $reporter->showSummary(); ?>
    </table>
</body>
</html>
