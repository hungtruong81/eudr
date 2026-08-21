"use client";
import React, { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Form, Input, InputNumber, message, Row, Col } from "antd";
import {
  createProductionGongCart,
  updateProductionGongCart,
  generateProductionGongCartCode,
} from "../actions";
import { IProductionGongCart } from "../types";
import { getFactory } from "../../factory/actions";
import { IFactory } from "../../factory/types";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface GongCartSheetProps {
  open: boolean;
  onClose: () => void;
  record: IProductionGongCart | null;
}

export const GongCartSheet = ({
  open,
  onClose,
  record,
}: GongCartSheetProps) => {
  const t = useTranslations("Factory.metadata.gong_cart");
  const tm = useTranslations("Factory.metadata");

  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const createMutation = useMutation({
    mutationFn: createProductionGongCart,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["gong-carts"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({
      gong_cart_code,
      data,
    }: {
      gong_cart_code: string;
      data: any;
    }) => updateProductionGongCart(gong_cart_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["gong-carts"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const fetchGeneratedCode = async () => {
    try {
      const res = await generateProductionGongCartCode();
      if (res?.data?.gong_cart_code) {
        form.setFieldValue("gong_cart_code", res.data.gong_cart_code);
      }
    } catch (error) {
      console.error("Error fetching gong cart code:", error);
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
        gong_cart_code: record.gong_cart_code,
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
              name="gong_cart_name"
              label={t("name")}
              rules={[{ required: true, message: t("name_required") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="gong_cart_code"
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
          <Col span={24}>
            <Form.Item
              name="max_poles"
              label={t("max_poles")}
              rules={[{ required: true, message: t("max_poles_required") }]}>
              <InputNumber
                style={{ width: "100%" }}
                min={1}
                placeholder={t("enter_max_poles")}
              />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};
