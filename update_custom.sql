-- 05.09.2014 10:23:49
insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('4e779b6e-7523-761d-7d94-4330d4982cfe', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'bc2c8307-85b4-3acd-833a-3fd1380b0ccb', 'ФИО', 'clientname', '0', NULL, NULL, NULL, NULL, NULL, '332cb042-111b-3598-4458-7b36a1d0b67f', '0', NULL, NULL, NULL);
-- 05.09.2014 10:23:50
alter table "iris_task" add "clientname" character varying(250);;
-- 05.09.2014 10:23:50
comment on column "iris_task"."clientname" is 'ФИО';;
-- 05.09.2014 10:25:57
insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('62980019-6f33-12ca-d1e2-8888782aa75d', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'bc2c8307-85b4-3acd-833a-3fd1380b0ccb', 'Пол', 'clientgenderid', '0', NULL, NULL, NULL, NULL, NULL, '9ab5af8c-0984-f1d3-53cd-ff3901ac72b1', '0', NULL, NULL, NULL);
-- 05.09.2014 10:25:58
alter table "iris_task" add "clientgenderid" character varying(36);;
-- 05.09.2014 10:25:58
comment on column "iris_task"."clientgenderid" is 'Пол';;
-- 05.09.2014 10:29:15
insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('840acf50-e03f-0060-adbb-e60a1a42e430', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'bc2c8307-85b4-3acd-833a-3fd1380b0ccb', 'E-mail', 'clientemail', '0', NULL, NULL, NULL, NULL, NULL, '332cb042-111b-3598-4458-7b36a1d0b67f', '0', NULL, NULL, NULL);
-- 05.09.2014 10:29:15
alter table "iris_task" add "clientemail" character varying(250);;
-- 05.09.2014 10:29:16
comment on column "iris_task"."clientemail" is 'E-mail';;
-- 05.09.2014 10:32:16
insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('15d84024-c0ff-2c99-08b7-f6c08c60c6ca', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'bc2c8307-85b4-3acd-833a-3fd1380b0ccb', 'Не звонить', 'clientdonotcall', '0', NULL, NULL, NULL, NULL, NULL, '687bc1a7-de12-ab78-11bd-6936d9a9ff75', '0', NULL, NULL, NULL);
-- 05.09.2014 10:32:16
alter table "iris_task" add "clientdonotcall" smallint;;
-- 05.09.2014 10:32:16
comment on column "iris_task"."clientdonotcall" is 'Не звонить';;
-- 05.09.2014 10:32:36
insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('9cb838d3-ff68-a6d7-315c-d35309914cfe', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), '58841eee-99d0-373f-b905-4031fef6c501', 'Не звонить', 'donotcall', '0', NULL, NULL, NULL, NULL, NULL, '687bc1a7-de12-ab78-11bd-6936d9a9ff75', '0', NULL, NULL, NULL);
-- 05.09.2014 10:32:36
alter table "iris_contact" add "donotcall" smallint;;
-- 05.09.2014 10:32:36
comment on column "iris_contact"."donotcall" is 'Не звонить';;
-- 05.09.2014 10:38:06
insert into iris_Table_Column (ID, createid, createdate, modifyid, modifydate, TableID, Name, Code, IsDuplicate, DefaultValue, fkName, fkTableID, pkName, IndexName, ColumnTypeID, isNotNull, OnDeleteID, OnUpdateID, Description) values ('3d5cb8a4-c9d8-ca42-3486-09d3ac00e620', '005405b7-8344-49f6-98a2-e1891cbff803', now(), '005405b7-8344-49f6-98a2-e1891cbff803', now(), 'bc2c8307-85b4-3acd-833a-3fd1380b0ccb', 'Мобильный', 'phone2', '0', NULL, NULL, NULL, NULL, NULL, '332cb042-111b-3598-4458-7b36a1d0b67f', '0', NULL, NULL, NULL);
-- 05.09.2014 10:38:06
alter table "iris_task" add "phone2" character varying(250);;
-- 05.09.2014 10:38:06
comment on column "iris_task"."phone2" is 'Мобильный';;
