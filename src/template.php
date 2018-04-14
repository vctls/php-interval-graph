<div class='foo' style='<?= isset($this->bgColor) ? ' background-color: ' . $this->bgColor . ';' : '' ?>'>
    <?php foreach ($vs as $k => $v) : ?>
        <?php if ($v[2] === $v[3]): // Isolated date.?>
            <div class='bar bar<?= $k; ?> bar-date' style='left:<?= $v[0] ?>%;' data-title='<?= $v[4] ?>'></div>
        <?php else: ?>
            <div class='bar bar<?= $k; ?> bar-intv'
                 style='left:<?= $v[0] ?>%; right:<?= 100 - $v[1] ?>%; background-color:<?= $v[6] ?>'
                 data-title="<?= $v[4] . ' ➔ ' . $v[5] . (isset($v[7]) ? ' : ' . $v[7] : '') ?>"
            >
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>