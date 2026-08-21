"use client";

import { BaseFilter } from "@/components/base-filter";
import { DatePicker, Form, Input, Select } from "antd";
import React, { useEffect } from "react";
import dayjs from "dayjs";
import { rangePresets } from "@/constants/range-date";
import { IGetProductLotInventoryParams } from "@/features/factory/lot/actions";
import { useTranslations } from "next-intl";

const { RangePicker } = DatePicker;

interface IInventoryFilterProps {
  onSearch: (params: Partial<IGetProductLotInventoryParams>) => void;
}

const InventoryFilter = ({ onSearch }: IInventoryFilterProps) => {
  const t = useTranslations("Inventory");
  const tCommon = useTranslations("Common");
  const tStatus = useTranslations("Status");
  const [form] = Form.useForm();

  useEffect(() => {
    form.setFieldsValue({
      dateRange: [dayjs().subtract(1, "month"), dayjs()],
    });
  }, [form]);

  const handleFinish = (values: any) => {
    const { dateRange, ...rest } = values;
    onSearch({
      ...rest,
      production_date_from: dateRange?.[0]?.format("YYYY-MM-DD") || "",
      production_date_to: dateRange?.[1]?.format("YYYY-MM-DD") || "",
    });
  };

  const handleReset = () => {
    form.resetFields();
    onSearch({
      page: 1,
      limit: 10,
      eudr_type: "all",
      lot_type: "all",
      production_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      production_date_to: dayjs().format("YYYY-MM-DD"),
    });
  };

  return (
    <BaseFilter
      form={form}
      onFinish={handleFinish}
      onReset={handleReset}
      resetTooltip={tCommon("clear_filter")}>
      <Form.Item name="search" className="mb-0" label={t("lot_code")}>
        <Input placeholder={t("search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item
        name="eudr_type"
        className="mb-0"
        label={t("eudr_type")}
        initialValue="all">
        <Select
          placeholder={t("eudr_type")}
          style={{ width: 140 }}
          allowClear
          options={[
            { label: t("all"), value: "all" },
            { label: "EUDR", value: "eudr" },
            { label: "Non-EUDR", value: "non_eudr" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="lot_type"
        className="mb-0"
        label={t("lot_type")}
        initialValue="all">
        <Select
          placeholder={t("lot_type")}
          style={{ width: 140 }}
          allowClear
          options={[
            { label: t("all"), value: "all" },
            { label: t("internal"), value: "internal" },
            { label: t("external"), value: "external" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="status"
        className="mb-0"
        label={t("status")}
        initialValue="all">
        <Select
          placeholder={t("status")}
          style={{ width: 140 }}
          allowClear
          options={[
            { label: tStatus("all"), value: "all" },
            { label: tStatus("draft"), value: "draft" },
            { label: tStatus("confirmed"), value: "confirmed" },
            { label: tStatus("shipped"), value: "shipped" },
            { label: tStatus("cancelled"), value: "cancelled" },
          ]}
        />
      </Form.Item>
      <Form.Item
        name="dateRange"
        className="mb-0"
        initialValue={[dayjs().subtract(1, "month"), dayjs()]}
        label={t("production_date")}>
        <RangePicker
          placeholder={[tCommon("from_date"), tCommon("to_date")]}
          format="DD/MM/YYYY"
          presets={rangePresets}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default InventoryFilter;
