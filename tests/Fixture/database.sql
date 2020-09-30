create table addresses
(
    id         varchar(255) not null,
    street     varchar(255) not null,
    number     varchar(255) not null,
    created_at datetime     not null,
    deleted_at datetime
);

create table order_products
(
    quantity       float        not null,
    order_id       varchar(255) not null,
    product_id     varchar(255) not null,
    price_value    float        not null,
    price_currency varchar(255) not null
);

create table orders
(
    id             varchar(255) not null,
    user_id        varchar(255) not null,
    total_value    float        not null,
    total_currency varchar(255) not null
);

create table products
(
    id             varchar(255) not null,
    price_value    float        not null,
    price_currency varchar(255) not null
);

create table users
(
    id         varchar(255) not null,
    email      varchar(255) not null,
    height     float        not null,
    age        int          not null,
    active     tinyint      not null,
    address_id varchar(255) not null
);

create table nullable_properties
(
    id              varchar(255) not null,
    email           varchar(255),
    height          float,
    amount_value    float,
    amount_currency varchar(255)
);

create table payments
(
    id              varchar(255) not null,
    amount_value    float        not null,
    amount_currency varchar(255) not null
);

create table payment_status
(
    payment_id varchar(255) not null,
    status     varchar(100) not null,
    at         datetime
);

