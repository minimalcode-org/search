Minimalcode Search
======================

Minimalistic stand-alone **PHP** implementation of **Spring Solr Data Criteria**.

The implementation is remade from scratch in PHP, but it follows 1:1 (for what it's possible)
the Spring API and features. The original authors are: Christoph Strobl, Philipp Jardas and
Francisco Spaeth.

**Criteria** is a single-class (yet very powerful) library that allows the construction of q=""
queries for Sorl\Lucene engine.

As a low-level abstraction, it should be compatible with theoretically **any client** out there (Solarium etc...).
It follows more or less a fluent API style, which allows to easily chain together multiple criteria.

### Features:
- Fluent query-builder
- Criteria pipeling and nesting
- Multi-dimensional Array support
- Range, Fuzzy, Sloppy search
- Geospatial (!bbox, !geofilt, range bbox) search
- 1-single-pulic-class, no dependencies
- Near 200 test assertions
- 100% test coverage

### Links:
- [Spring Data](http://projects.spring.io/spring-data)
- [Apache Solr](http://lucene.apache.org/solr/)

Examples
-----------

The string is generated with **$criteria->getQuery()** or casting **(string) $criteria**;

```php
    Criteria::where('foo')->is('bar');
    // foo:bar

    // ==========================================

    Criteria::where('foo')->startsWith('starts')->endsWith('ends')->in(['one', 'two'])->boost(2.5);
    // foo:(starts* *ends one two)^2.5

    // ==========================================

    Criteria::where('foo')->is('bar')->not()
                 ->andWhere('zoo')->is('zar');
    // -foo:bar AND zoo:zar

    // ==========================================

    $nearNorthPole  = Criteria::where('position')->nearCircle(38.116181, -86.929463, 100.5);
    // "{!bbox pt=38.116181,\\-86.929463 sfield=position d=100.5}"

    // ==========================================

    $santaClaus = Criteria::where('santa-name')->contains(['Noel', 'Claus', 'Natale', 'Baba', 'Nicolas'])
                ->andWhere('santa-beard-exists')->is(true)
                ->andWhere('santa-beard-lenght')->between(5.5, 10.0)
                ->andWhere('santa-beard-color')->startsWith('whi')->endsWith('te')
                ->andWhere($nearNorthPole);

    // "santa-name:(*Noel* *Claus* *Natale* *Baba* *Nicolas*)
    //      AND santa-beard-exists:true
    //      AND santa-beard-lenght:[5.5 TO 10]
    //      AND santa-beard-color:(whi* *te)
    //      AND {!bbox pt=38.116181,\\-86.929463 sfield=position d=100.5}"

    // ==========================================

    $goodPeople = Criteria::where('good-actions')->greaterThanEqual(10)
                        ->orWhere('bad-actions')->lessThanEqual(5);

    // 'good-actions:[10 TO *] OR bad-actions:[* TO 5]'

    // ==========================================

    $gifts = Criteria::where('gift-name')->sloppy('LED TV GoPro Oculus Tablet Laptop', 2)
                    ->andWhere('gift-type')->fuzzy('information', 0.4)->startsWith('tech')
                    ->andWhere('__query__')->expression('{!dismax qf=myfield}how now brown cow');

    // 'gift-name:"LED TV GoPro Oculus Tablet Laptop"~2
    //      AND gift-type:(information~0.4 tech*)
    //      AND __query__:{!dismax qf=myfield}how now brown cow'

    // ==========================================

    // **Warning: Things escalates very fast here :)**
    $christmas = new DateTime('2016-12-25');
    $contributors = ['Christoph', 'Philipp', 'Francisco', 'Fabio'];
    $giftReceivers  = Criteria::where('gift-received')->is(null)
                            ->andWhere('chimney')->isNotNull()
                            ->andWhere('date')->is($christmas)->greaterThanEqual(new \Datetime('1970-01-01'))
                            ->andWhere($santaClaus)
                            ->andWhere($gifts)
                            ->andWhere(
                                Criteria::where('name')->in($contributors)->boost(2.0)
                                        ->orWhere($goodPeople)
                            );

    // "-gift-received:[* TO *]
    //      AND chimney:[* TO *]
    //      AND date:(2016\\-12\\-25T00\\:00\\:00Z [1970\\-01\\-01T00\\:00\\:00Z TO *])
    //      AND (santa-name:(*Noel* *Claus* *Natale* *Baba* *Nicolas*)
    //                AND santa-beard-exists:true 
    //                AND santa-beard-lenght:[5.5 TO 10]
    //                AND santa-beard-color:(whi* *te) 
    //                AND {!bbox pt=38.116181,\\-86.929463 sfield=position d=100.5}
    //      )
    //      AND (gift-name:\"LED TV GoPro Oculus Tablet Laptop\"~2 
    //                AND gift-type:(information~0.4 tech*)
    //                AND __query__:{!dismax qf=myfield}how now brown cow
    //      )
    //      AND (name:(Christoph Philipp Francisco Fabio)^2.0
    //                OR (good-actions:[10 TO *] 
    //                          OR bad-actions:[* TO 5]
    //                )
    //     )"
```

And there is more features... See CriteriaReadmeTest for additional examples.