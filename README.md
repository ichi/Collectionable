# Collectionable Plugin #

元はこちら [hiromi2424/Collectionable](http://github.com/hiromi2424/Collectionable) =)


## optionsBehavior and scopeBehavior


table
    +----+----------------+-----+-------+------------+------------+
    | id | name           | age | level | created    | modified   |
    +----+----------------+-----+-------+------------+------------+
    |  1 | Haleigh Kirlin |  21 |    20 | 2010-02-28 | 2011-02-20 |
    +----+----------------+-----+-------+------------+------------+
    |  2 | Raphaelle Bode |  16 |    15 | 2010-04-04 | 2010-09-07 |
    +----+----------------+-----+-------+------------+------------+
    |  3 | Deontae Rogahn |  27 |    15 | 2010-09-17 | 2011-04-05 |
    +----+----------------+-----+-------+------------+------------+
    |  4 | Lewis Little   |  16 |    20 | 2010-12-20 | 2011-01-20 |
    +----+----------------+-----+-------+------------+------------+
    |  5 | Polly Hegmann  |  22 |    22 | 2011-01-11 | 2011-02-18 |
    +----+----------------+-----+-------+------------+------------+



Model
    public $actsAs = array('collectionable.Options', 'collectionable.Scope');
    public function setOptions(){
        return array(
            'latest' => array(
                'order' => array("{$this->alias}.created"=>'desc'),
            ),
            'highLevel' => array(
                'order' => array("{$this->alias}.level"=>'desc'),
            ),
            'adult' => array(
                'conditions' => array(
                    "{$this->alias}.age >" => 20,
                ),
            ),
            'younger' => create_function('$age', <<<EOS
                return array(
                    'conditions' => array(
                        '{$this->alias}.age <' => \$age,
                    ),
                );
    EOS
            ),
            'levelIs' => create_function('$level', <<<EOS
                return array(
                    'conditions' => array(
                        '{$this->alias}.level' => \$level,
                    ),
                );
    EOS
            ),
            'YoungerAndLevelIs' => create_function('$age,$level', <<<EOS
                return array(
                    'options' => array(
                        'younger' => \$age,
                        'levelIs' => \$level,
                    ),
                );
    EOS
            ),
        );
    }


例1:
    $Model->find('all', array(
        'options'=>array(
            'latest',
            'younger'=>17,
        ),
    ));
    //or
    $Model->scope()
        ->latest()
        ->younger(17)
        ->all();

結果1:
    +----+----------------+-----+-------+------------+------------+
    |  4 | Lewis Little   |  16 |    20 | 2010-12-20 | 2011-01-20 |
    +----+----------------+-----+-------+------------+------------+
    |  2 | Raphaelle Bode |  16 |    15 | 2010-04-04 | 2010-09-07 |
    +----+----------------+-----+-------+------------+------------+

例2:
    $Model->find('all', array(
        'options'=>array(
            'youngerAndLevelIs'=>array(22, 20),
        ),
    ));
    //or
    $Model->scope()
        ->youngerAndLevelIs(22, 20)
        ->all();

結果2:
    +----+----------------+-----+-------+------------+------------+
    |  1 | Haleigh Kirlin |  21 |    20 | 2010-02-28 | 2011-02-20 |
    +----+----------------+-----+-------+------------+------------+
    |  4 | Lewis Little   |  16 |    20 | 2010-12-20 | 2011-01-20 |
    +----+----------------+-----+-------+------------+------------+

例3:
    $Model->find('first', array(
        'options'=>array(
            'adult',
            'highLevel',
        ),
    ));
    //or
    $Model->scope()
        ->adult()
        ->highLevel()
        ->first();

結果3:
    +----+----------------+-----+-------+------------+------------+
    |  1 | Haleigh Kirlin |  21 |    20 | 2010-02-28 | 2011-02-20 |
    +----+----------------+-----+-------+------------+------------+


例 パラメータ取得:
    $params = $Model->options(array(
        'adult',
        'levelIs'=>22,
        'latest',
    ));
    //or
    $params = $Model->scope()
        ->adult()
        ->levelIs(22)
        ->latest()
        ->get();

結果 パラメータ
    array(
        'conditions' => array(
            'Model.age >' => 20,
            'Model.level' => 22,
        ),
        'order' => array('Model.created'=>'desc'),
    )




## License

Licensed under The MIT License.
Redistributions of files must retain the above copyright notice.


Copyright 2010 hiromi, https://github.com/hiromi2424

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
