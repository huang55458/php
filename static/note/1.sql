show variables  like 'log_%';
show binlog events in 'mysql-bin.000002'  ;
show binary logs ;
show master status ;
flush logs; -- 重新生成一个文件记录日志


show VARIABLES like '%log%';
show BINARY Logs;
-- mysqlbinlog.exe  --base64-output=decode-rows -v mysql-bin.000002 | grep update | grep user > tmp.log


select * from user_copy lock in share mode ; -- 共享锁
select * from user_copy for update ; -- 排他锁
select current_timestamp ;
select unix_timestamp() ;
select FROM_UNIXTIME(1688782012 ,'%Y-%m-%d %H:%i:%s');


start transaction;
alter table user_copy modify column password varchar(255);
insert into user_copy (name,password,sex) values ('test2', 'test23','5');
delete from user_copy where id = 3;
select * from user_copy where id in (7,8,9) for update ; # 当前读，加锁防止数据被更改
rollback;
commit;
select @@tx_isolation;
set session transaction isolation level repeatable read;
-- Mysql 当前读 MVCC机制不能完全解决幻读问题，当一个事务修改了其他事务已经提交的数据/当前读时其他事务插入数据后使用快照读 时仍会产生幻读     https://www.xiaolincoding.com/mysql/transaction/phantom.html#%E7%AC%AC%E4%B8%80%E4%B8%AA%E5%8F%91%E7%94%9F%E5%B9%BB%E8%AF%BB%E7%8E%B0%E8%B1%A1%E7%9A%84%E5%9C%BA%E6%99%AF


lock tables user_copy write ;
unlock tables ;
select now();
select unix_timestamp();
select from_unixtime(unix_timestamp());

create function test_f(i int) returns int
begin
    return i*2;
end;
select * from test.user where id < test_f(4);


alter table user modify column mark text character set utf8mb4 null comment 'test';



create procedure insert_data15(in num integer) -- 创建num条数据
begin
    set @id = 1;
    LOOP_LABLE:loop
        set @id = @id + 1;
        set @t = @id % 2;
        case @t
            when 0 then
                set @sex = '女';
            when 1 then
                set @sex = '男';
            end case;

        insert into user values (id, @sex, concat('小黑', @id), concat('123456', @id),'{"name": 2}', '{"goods": [{"num": "1", "pkg": "纸箱", "name": "货物", "unit_p": "11", "volume": 11, "weight": "11", "wv_ratio": 0.001, "unit_p_unit": "per_num", "price_weight": 3666.667, "settle_weight": 3666.667, "subtotal_price": 11}], "cee_id": "6", "arr_info": {"street": "成都", "show_val": "成都"}, "cee_name": "策策", "trsp_mode": "汽运", "cee_mobile": "135468498653", "start_info": {"poi": "", "city": "宜春市", "adcode": "", "street": "", "district": "", "province": "江西省", "show_val": "宜春市"}, "receipt_cat": "receipt", "service_type": "site_site", "cee_addr_info": {"poi": "114.981381,27.108032", "city": "吉安市", "adcode": "360802", "street": "文山街道", "district": "吉州区", "province": "江西省", "show_val": "吉安市吉州区江西吉安长运有限公司"}, "need_dispatch": 2}','{"name": 2}',date_format(current_timestamp ,'%Y-%m-%d %H:%i:%s'),date_format(current_date ,'%Y-%m-%d %H:%i:%s'));
        if @id > num then
            leave LOOP_LABLE;
        end if;

    end loop;
end;

call insert_data15(1000);
show procedure status;
show create procedure insert_data15;
