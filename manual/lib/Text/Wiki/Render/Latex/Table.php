<?php

class Text_Wiki_Render_Latex_Table extends Text_Wiki_Render {
    var $cell_id    = 0;
    var $cell_count = 0;
    var $is_spanning = false;

    var $conf = array(
                      'css_table' => null,
                      'css_tr' => null,
                      'css_th' => null,
                      'css_td' => null
                      );

    /**
     *
     * Renders a token into text matching the requested format.
     *
     * @access public
     *
     * @param array $options The "options" portion of the token (second
     * element).
     *
     * @return string The text rendered from the token options.
     *
     */

    function token($options)
    {
        // make nice variable names (type, attr, span)
        extract($options);

        switch ($type)
            {
            case 'table_start':
                $this->cell_count = $cols;
                
                $max_width = 60;
                
                $available_width = $max_width;
                if (count($col_widths) > 0) {
                    $avg_width = $available_width / (float) count($col_widths);
                }
                $calc_col_widths = array();
                
                while (count($col_widths) > 0) {

                    $found_thinner = false;
                    
                    foreach ($col_widths as $k => $col_width) {
                        if ($col_width <= $avg_width) {
                            $found_thinner = true;
                            $available_width -= $col_width;
                            $calc_col_widths[$k] = $col_width / (float) $max_width;
                            unset($col_widths[$k]);
                        }
                    }
    
                    if (count($col_widths) > 0) {
                        $avg_width = $available_width / (float) count($col_widths);
                    }
                    
                    if ( ! $found_thinner) {
                        foreach ($col_widths as $k => $col_width) {
                            $calc_col_widths[$k] = $avg_width / (float) $max_width;
                            unset($col_widths[$k]);
                        }
                    }
                }
                                    
                $tbl_start = '{\centering \begingroup' . "\n"
                           . '\setlength{\newtblsparewidth}{\linewidth-' . 2 * ($cols + 1) . '\tabcolsep}' . "\n"
                           . '\begin{longtable}{|';
                           
                for ($a=0; $a < $this->cell_count; $a++) {
                    $tbl_start .= 'p{' . round($calc_col_widths[$a + 1], 4) . '\newtblsparewidth}|';
                }
                $tbl_start .= "}\n";

                return $tbl_start;

            case 'table_end':
                return "\\hline\n\\end{longtable}\n\\endgroup}\n\n";

            case 'caption_start':
                return "\\caption{";

            case 'caption_end':
                return "}\n";

            case 'row_start':
                $this->is_spanning = false;
                $this->cell_id = 0;
                return "\\hline\n";

            case 'row_end':
                return "\\\\\n";

            case 'cell_start':
                if ($span > 1) {
                    $col_spec = '';
                    if ($this->cell_id == 0) {
                        $col_spec = '|';
                    }
                    $col_spec .= 'l|';

                    $this->cell_id += $span;
                    $this->is_spanning = true;

                    return '\multicolumn{' . $span . '}{' . $col_spec . '}{';
                }

                $this->cell_id += 1;
                
                if ($attr === 'header') {
                    return '\bfseries ';
                } else {
                    return '';
                }

            case 'cell_end':
                $out = '';
                if ($this->is_spanning) {
                    $this->is_spanning = false;
                    $out = '}';
                }

                if ($this->cell_id != $this->cell_count) {
                    $out .= ' & ';
                }

                return $out;

            default:
                return '';

            }
    }
}
?>
