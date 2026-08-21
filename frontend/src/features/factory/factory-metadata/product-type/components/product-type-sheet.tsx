"use client";
import React, { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Form,
  Input,
  message,
  Row,
  Col,
  Select,
} from "antd";
import {
  createProductType,
  updateProductType,
} from "../action";
import { IProductType } from "../types";
import BaseSheet from "@/components/shared/base-sheet";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface ProductTypeSheetProps {
  open: boolean;
  onClose: () => void;
  record: IProductType | null;
}

export const ProductTypeSheet = ({
  open,
  onClose,
  record,
}: ProductTypeSheetProps) => {
  const t = useTranslations("Factory.metadata.product_type");
  
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const createMutation = useMutation({
    mutationFn: createProductType,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["product-types"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({
      product_type_code,
      data,
    }: {
      product_type_code: string;
      data: any;
    }) => updateProductType(product_type_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["product-types"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  useEffect(() => {
    if (open && record) {
      form.setFieldsValue(record);
    } else if (open) {
      form.resetFields();
    }
  }, [open, record, form]);

  const onFinish = (values: any) => {
    if (record) {
      updateMutation.mutate({
        product_type_code: record.product_type_code,
        data: values,
      });
    } else {
      createMutation.mutate(values);
    }
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? t("edit_title") : t("add_title")}
      loading={createMutation.isPending || updateMutation.isPending}
      width={600}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              name="product_type_name"
              label={t("name")}
              rules={[
                { required: true, message: t("enter_name") },
              ]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="product_type_code"
              label={t("code")}
              rules={[
                { required: true, message: t("enter_code") },
              ]}>
              <Input placeholder={t("enter_code")} disabled={!!record} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="product_type_category"
              label={t("category")}
              rules={[{ required: true, message: t("select_category") }]}>
              <Select
                placeholder={t("select_category")}
                options={[
                  { value: "scrap_rubber", label: t("scrap_rubber") },
                  { value: "concentrated_latex", label: t("concentrated_latex") },
                ]}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="product_weight"
              label={t("weight")}
              rules={[{ required: true, message: t("enter_weight") }]}>
              <Input placeholder="VD: 33.33" />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item name="description" label={t("description")}>
              <Input.TextArea rows={3} placeholder={t("enter_description")} />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};
