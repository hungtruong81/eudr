"use client";
import React, { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import {
  Form,
  Input,
  message,
  Row,
  Col,
  InputNumber,
  Select,
} from "antd";
import {
  createProductTank,
  updateProductTank,
} from "../actions";
import { IProductTank } from "../types";
import { getFactory } from "../../factory/actions";
import { IFactory } from "../../factory/types";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface ProductTankSheetProps {
  open: boolean;
  onClose: () => void;
  record: IProductTank | null;
}

export const ProductTankSheet = ({
  open,
  onClose,
  record,
}: ProductTankSheetProps) => {
  const t = useTranslations("Factory.metadata.product_tank");
  const tm = useTranslations("Factory.metadata");
  const tc = useTranslations("Common");
  
  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const createMutation = useMutation({
    mutationFn: createProductTank,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["product-tank"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({
      product_tank_code,
      data,
    }: {
      product_tank_code: string;
      data: any;
    }) => updateProductTank(product_tank_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["product-tank"] });
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
        product_tank_code: record.product_tank_code,
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
      width={600}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={24}>
            <Form.Item
              name="product_tank_name"
              label={t("name")}
              rules={[
                { required: true, message: t("name_required") },
              ]}>
              <Input placeholder={t("enter_name")} />
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
          <Col span={12}>
            <Form.Item
              name="product_type"
              label={t("product_type")}
              rules={[
                { required: true, message: t("product_type_required") },
              ]}>
              <Select
                placeholder={t("select_product_type")}
                options={[
                  { value: "SVR 3L", label: "SVR 3L" },
                  { value: "SVR 5", label: "SVR 5" },
                  { value: "SVR 10", label: "SVR 10" },
                  { value: "SVR 20", label: "SVR 20" },
                ]}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="capacity"
              label={t("capacity_max")}
              rules={[{ required: true, message: t("capacity_required") }]}>
              <InputNumber
                style={{ width: "100%" }}
                min={0}
                placeholder="VD: 5000"
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="location"
              label={t("location")}
              rules={[{ required: true, message: t("location_required") }]}>
              <Input placeholder="VD: Khu B" />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item name="notes" label={tc("notes")}>
              <Input.TextArea rows={3} placeholder={tc("no_notes")} />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};
