"use client";
import React, { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Form, Input, message, Row, Col } from "antd";
import {
  createProductionRoller,
  updateProductionRoller,
  generateProductionRollerCode,
} from "../actions";
import { IProductionRoller } from "../types";
import { getFactory } from "../../factory/actions";
import { IFactory } from "../../factory/types";
import BaseSheet from "@/components/shared/base-sheet";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

interface ProductRollerSheetProps {
  open: boolean;
  onClose: () => void;
  record: IProductionRoller | null;
}

export const ProductRollerSheet = ({
  open,
  onClose,
  record,
}: ProductRollerSheetProps) => {
  const t = useTranslations("Factory.metadata.product_roller");
  const tm = useTranslations("Factory.metadata");

  const [form] = Form.useForm();
  const queryClient = useQueryClient();

  const createMutation = useMutation({
    mutationFn: createProductionRoller,
    onSuccess: () => {
      message.success(t("create_success"));
      queryClient.invalidateQueries({ queryKey: ["product-rollers"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ roller_code, data }: { roller_code: string; data: any }) =>
      updateProductionRoller(roller_code, data),
    onSuccess: () => {
      message.success(t("update_success"));
      queryClient.invalidateQueries({ queryKey: ["product-rollers"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const fetchGeneratedCode = async () => {
    try {
      const res = await generateProductionRollerCode();
      if (res?.data?.roller_code) {
        form.setFieldValue("roller_code", res.data?.roller_code);
      }
    } catch (error) {
      console.error("Error fetching roller code:", error);
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
        roller_code: record.roller_code,
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
              name="roller_name"
              label={t("name")}
              rules={[{ required: true, message: t("name_required") }]}>
              <Input placeholder={t("enter_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="roller_code"
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
