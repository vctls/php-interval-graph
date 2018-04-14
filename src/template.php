<div class="intvg" style="position: relative; width: 100%; height: 20px;
        <?= isset($this->bgColor) ? ' background-color: ' . $this->bgColor . ';' : '' ?>">
    <?php foreach ($vs as $k => $v) : ?>
        <?php if ($v[2] === $v[3]): // Isolated date.?>
            <div class="bar bar<?= $k; ?>" style="position: absolute; height: 20px; box-sizing: content-box;
                border-width: 0 2px 0 2px;
                border-style: solid;
                border-color: black;
                left:  <?= $v[0] ?>%;
                width: 0;"
                 data-title="<?= $v[4] ?>"
            >
            </div>
        <?php else: ?>
            <div class="bar bar<?= $k; ?>" style="position: absolute; height: 20px;
                left:  <?= $v[0] ?>%;
                right: <?= 100 - $v[1] ?>%;
                /*width: <?= $v[1] - $v[0] ?>%;*/
                background-color: <?= $this->getColor($v[6]) ?>"
                 data-title="<?=
                 $v[4]
                 . ' ➔ ' .
                 $v[5]
                 . (isset($v[7]) ? ' : ' . $v[7] : '')
                 ?>"
            >
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>