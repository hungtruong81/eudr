"use client";
import React, { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Form, Input, message, Row, Col } from "antd";
import {
  createProductionOven,
  updateProductionOven,
  generateProductionOvenCode,
} from "../actions";
import { IProductionOven } from "../types";
import { getFactory } from "../../factory/actions";
import { IFactory } from "../../factory/types";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface ProductOvenSheetProps {
  open: boolean;
  onClose: () => void;
  record: IProductionOven | null;
}

export const ProductOvenSheet = ({
  open,
  onClose,
  record,
}: ProductOvenSheetProps) => {
  const t = useTranslations("Factory.metadata.product_oven");
  const tm = useTranslations("Factory.metadata");

  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const createMutation = useMutation({
    mutationFn: createProductionOven,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["product-ovens"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ oven_code, data }: { oven_code: string; data: any }) =>
      updateProductionOven(oven_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["product-ovens"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const fetchGeneratedCode = async () => {
    try {
      const res = await generateProductionOvenCode();
      if (res?.data?.oven_code) {
        form.setFieldValue("oven_code", res.data.oven_code);
      }
    } catch (error) {
      console.error("Error fetching oven code:", error);
    }
  };

  useEffect(() => {
    if (open) {
      if (record) {
        form.setFieldsValue(record);
      } else {
        form.resetFields();
        fetchGeneratedCode();
      }
    }
  }, [open, record, form]);

  const onFinish = (values: any) => {
    if (record) {
      updateMutation.mutate({
        oven_code: record.oven_code,
        data: values,
      });
    } else {
      createMutation.mutate(values);
    }
  };

  const mapFactoryOptions = (item: IFactory) => ({
    value: item.factory_id.toString(),
    label: item.factory_name,
  });

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? t("edit_title") : t("add_title")}
      loading={createMutation.isPending || updateMutation.isPending}
      width={500}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="oven_name"
              label={t("name")}
              rules={[{ required: true, message: t("name_required") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="oven_code"
              label={t("code")}
              rules={[{ required: true, message: t("code_required") }]}>
              <Input
                placeholder={t("enter_code")}
                className="uppercase"
                disabled
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="factory_id"
              label={t("factory")}
              rules={[{ required: true, message: tm("factory_required") }]}>
              <InfiniteScrollSelect<IFactory>
                queryKey={["factories-select"]}
                fetchFn={(p) => getFactory({ page: p.page, limit: p.limit })}
                mapOption={mapFactoryOptions}
                placeholder={tm("select_factory")}
                allowClear
              />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};
