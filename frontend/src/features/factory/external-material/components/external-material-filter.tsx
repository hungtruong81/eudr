"use client";
import {
  DatePicker,
  Form,
  FormInstance,
  Select,
} from "antd";
import React from "react";
import dayjs from "dayjs";
import { BaseFilter } from "@/components/base-filter";
import { useTranslations } from "next-intl";

const { RangePicker } = DatePicker;

interface Props {
  filterForm: FormInstance;
  handleFilter: (values: any) => void;
  handleResetFilter: () => void;
}

const ExternalMaterialFilter = ({
  filterForm,
  handleFilter,
  handleResetFilter,
}: Props) => {
  const t = useTranslations("Factory.external_material");
  const ts = useTranslations("Status");
  const tc = useTranslations("Common");

  const onFinish = (values: any) => {
    const formattedValues: any = {
      status: values.status,
    };

    if (values.dateRange && values.dateRange.length === 2) {
      formattedValues.start_date = dayjs(values.dateRange[0]).format(
        "YYYY-MM-DD",
      );
      formattedValues.end_date = dayjs(values.dateRange[1]).format(
        "YYYY-MM-DD",
      );
    }

    handleFilter(formattedValues);
  };

  return (
    <BaseFilter
      onFinish={onFinish}
      onReset={handleResetFilter}
      loading={false}
      form={filterForm}>
      <Form.Item name="status" label={t("status")} style={{ marginBottom: 0 }}>
        <Select
          allowClear
          placeholder={tc("select_status")}
          style={{ width: 120 }}
          options={[
            { label: tc("all"), value: "all" },
            { label: ts("draft"), value: "draft" },
            { label: ts("pending"), value: "pending" },
            { label: ts("confirmed"), value: "confirmed" },
            { label: ts("cancelled"), value: "cancelled" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="dateRange"
        label={t("buy_time")}
        style={{ marginBottom: 0 }}>
        <RangePicker style={{ width: "100%" }} format="DD/MM/YYYY" />
      </Form.Item>
    </BaseFilter>
  );
};

export default ExternalMaterialFilter;
