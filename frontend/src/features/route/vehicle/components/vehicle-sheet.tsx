"use client";
import React, { useEffect } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Form, Input, message, Row, Col, InputNumber, Select } from "antd";
import { createVehicle, getBrands, updateVehicle } from "../actions";
import { IVehicle } from "../types";
import BaseSheet from "@/components/shared/base-sheet";
import { handleApiError } from "@/lib/api-error";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";

import { useTranslations } from "next-intl";

interface VehicleSheetProps {
  open: boolean;
  onClose: () => void;
  record: IVehicle | null;
}

export const VehicleSheet = ({ open, onClose, record }: VehicleSheetProps) => {
  const [form] = Form.useForm();
  const queryClient = useQueryClient();
  const tVehicle = useTranslations("Route.vehicle");

  const createMutation = useMutation({
    mutationFn: createVehicle,
    onSuccess: () => {
      message.success(tVehicle("create_success"));
      queryClient.invalidateQueries({ queryKey: ["vehicles"] });
      onClose();
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ vehicle_id, data }: { vehicle_id: string; data: any }) =>
      updateVehicle(vehicle_id, data),
    onSuccess: () => {
      message.success(tVehicle("update_success"));
      queryClient.invalidateQueries({ queryKey: ["vehicles"] });
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
        vehicle_id: record.vehicle_code,
        data: values,
      });
    } else {
      createMutation.mutate(values);
    }
  };
  const { data } = useQuery({
    queryKey: ["brands"],
    queryFn: () => getBrands({ page: 1, limit: 10 }),
  });

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      onOk={() => form.submit()}
      title={record ? tVehicle("edit_vehicle") : tVehicle("add_vehicle")}
      loading={createMutation.isPending || updateMutation.isPending}
      width={600}>
      <Form form={form} layout="vertical" onFinish={onFinish}>
        <Row gutter={16}>
          <Col span={12}>
            <Form.Item
              name="vehicle_name"
              label={tVehicle("vehicle_name")}
              rules={[{ required: true, message: tVehicle("enter_vehicle_name") }]}>
              <Input placeholder={tVehicle("enter_vehicle_name")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="license_plate"
              label={tVehicle("license_plate")}
              rules={[{ required: true, message: tVehicle("enter_license_plate") }]}>
              <Input placeholder={tVehicle("enter_license_plate")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="brand"
              label={tVehicle("brand")}
              rules={[{ required: true, message: tVehicle("select_brand") }]}>
              <Select
                options={data?.data?.map((item) => ({
                  value: item.vehicle_brand_name,
                  label: item.vehicle_brand_name,
                }))}
                placeholder={tVehicle("select_brand")}
              />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="type"
              label={tVehicle("type")}
              rules={[{ required: true, message: tVehicle("enter_type") }]}>
              <Input placeholder={tVehicle("enter_type")} />
            </Form.Item>
          </Col>
          <Col span={12}>
            <Form.Item
              name="manufacture_year"
              label={tVehicle("manufacture_year")}
              rules={[
                { required: true, message: tVehicle("enter_year") },
              ]}>
              <InputNumber
                placeholder={tVehicle("enter_year")}
                style={{ width: "100%" }}
                min={1900}
                max={new Date().getFullYear()}
              />
            </Form.Item>
          </Col>
        </Row>
      </Form>
    </BaseSheet>
  );
};
