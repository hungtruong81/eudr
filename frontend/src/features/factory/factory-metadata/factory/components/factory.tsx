"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Form, Input, message, Modal, Space } from "antd";
import React, { useEffect, useState } from "react";
import {
  createFactory,
  deleteFactory,
  getFactory,
  updateFactory,
} from "../actions";
import { CommonPaginationParams } from "@/types/api";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { IFactory } from "../types";
import { TooltipButton } from "@/components/tooltip-button";
import { DeleteOutlined, EditOutlined, PlusOutlined } from "@ant-design/icons";
import BaseSheet from "@/components/shared/base-sheet";
import { handleApiError } from "@/lib/api-error";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const Factory = () => {
  const t = useTranslations("Factory.metadata.factory");
  const tc = useTranslations("Common");

  const [params, setParams] = useState<CommonPaginationParams>({
    page: 1,
    limit: 10,
  });
  const { factory } = usePermissions();
  const [open, setOpen] = useState(false);
  const [record, setRecord] = useState<IFactory | null>(null);
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["factory", params],
    queryFn: () => getFactory(params),
  });

  const createMutation = useMutation({
    mutationFn: createFactory,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["factory"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ factory_code, data }: { factory_code: string; data: any }) =>
      updateFactory(factory_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["factory"] });
      handleClose();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteFactory,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["factory"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleDelete = (record: IFactory) => {
    deleteMutation.mutate(record.factory_code);
  };

  const handleEdit = (record: IFactory) => {
    setRecord(record);
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
    setRecord(null);
    form.resetFields();
  };

  useEffect(() => {
    if (open && record) {
      form.setFieldsValue(record);
    }
  }, [open, record, form]);

  const onFinish = (values: any) => {
    if (record) {
      updateMutation.mutate({
        factory_code: record.factory_code,
        data: values,
      });
    } else {
      createMutation.mutate(values);
    }
  };

  const columns: CustomColumnTypeTable<IFactory>[] = [
    {
      title: t("name"),
      dataIndex: "factory_name",
    },
    {
      title: t("address"),
      dataIndex: "address",
    },

    {
      title: tc("created_at"),
      dataIndex: "created_at",
      type: "date",
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {(factory.full || factory.update) && (
            <TooltipButton
              tooltip={t("edit_tooltip")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
            />
          )}

          {(factory.full || factory.delete) && (
            <ConfirmTooltipButton
              confirmTitle={t("confirm_delete_title")}
              confirmDescription={t("confirm_delete_desc", {
                name: record.factory_name,
              })}
              tooltip={t("delete_tooltip")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => handleDelete(record)}
              loading={
                deleteMutation.isPending &&
                deleteMutation.variables === record.factory_code
              }
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex justify="end">
        {(factory.full || factory.create) && (
          <TooltipButton
            tooltip={t("add_tooltip")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => setOpen(true)}>
            {tc("add")}
          </TooltipButton>
        )}
      </Flex>
      <CustomTable<IFactory>
        rowKey="factory_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="factory-list-table"
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onChange={(pagination) => {
          setParams({
            ...params,
            page: pagination.current || 1,
            limit: pagination.pageSize || 10,
          });
        }}
        scroll={{
          x: "max-content",
        }}
      />

      <BaseSheet
        open={open}
        onClose={handleClose}
        onOk={() => form.submit()}
        title={record ? t("edit_title") : t("add_title")}
        loading={createMutation.isPending || updateMutation.isPending}
        width={600}>
        <Form form={form} layout="vertical" onFinish={onFinish}>
          <Form.Item
            name="factory_name"
            label={t("name")}
            rules={[{ required: true, message: t("name_required") }]}>
            <Input placeholder={t("enter_name")} />
          </Form.Item>
          <Form.Item
            name="address"
            label={t("address")}
            rules={[{ required: true, message: t("address_required") }]}>
            <Input.TextArea rows={2} placeholder={t("enter_address")} />
          </Form.Item>
          <Form.Item name="notes" label={t("notes")}>
            <Input.TextArea rows={3} placeholder={t("enter_notes")} />
          </Form.Item>
        </Form>
      </BaseSheet>
    </Space>
  );
};

export default Factory;
