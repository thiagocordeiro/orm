create table addresses
(
    id varchar not null,
    street varchar not null,
    number varchar not null,
    created_at datetime not null
);

create table order_products
(
    quantity float not null,
    order_id string not null,
    product_id string not null,
    price_value float not null,
    price_currency varchar not null
);

create table orders
(
    id varchar not null,
    user_id string not null,
    total_value float not null,
    total_currency varchar not null
);

create table products
(
    id varchar not null,
    price_value float not null,
    price_currency varchar not null
);

create table users
(
    id varchar not null,
    email varchar not null,
    height float not null,
    age int not null,
    active tinyint not null,
    address_id string not null
);

