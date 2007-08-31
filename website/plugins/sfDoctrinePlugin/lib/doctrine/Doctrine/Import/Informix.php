<?php
/*
 *  $Id: Informix.php 1080 2007-02-10 18:17:08Z romanb $
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
Doctrine::autoload('Doctrine_Import');
/**
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision: 1080 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Import_Informix extends Doctrine_Import
{
	protected $sql = array(
                    'listTables'          => "SELECT tabname,tabtype FROM systables WHERE tabtype IN ('T','V') AND owner != 'informix'",
                    'listColumns'         => "SELECT c.colname, c.coltype, c.collength, d.default, c.colno
		                                      FROM syscolumns c, systables t,outer sysdefaults d
		                                      WHERE c.tabid = t.tabid AND d.tabid = t.tabid AND d.colno = c.colno
		                                      AND tabname='%s' ORDER BY c.colno",
		            'listPk'              => "SELECT part1, part2, part3, part4, part5, part6, part7, part8 FROM
		                                      systables t, sysconstraints s, sysindexes i WHERE t.tabname='%s'
		                                      AND s.tabid=t.tabid AND s.constrtype='P'
		                                      AND i.idxname=s.idxname",
                    'listForeignKeys'     => "SELECT tr.tabname,updrule,delrule,
                                              i.part1 o1,i2.part1 d1,i.part2 o2,i2.part2 d2,i.part3 o3,i2.part3 d3,i.part4 o4,i2.part4 d4,
                                              i.part5 o5,i2.part5 d5,i.part6 o6,i2.part6 d6,i.part7 o7,i2.part7 d7,i.part8 o8,i2.part8 d8
                                              from systables t,sysconstraints s,sysindexes i,
                                              sysreferences r,systables tr,sysconstraints s2,sysindexes i2
                                              where t.tabname='%s'
                                              and s.tabid=t.tabid and s.constrtype='R' and r.constrid=s.constrid
                                              and i.idxname=s.idxname and tr.tabid=r.ptabid
                                              and s2.constrid=r.primary and i2.idxname=s2.idxname",
                                        );

}
