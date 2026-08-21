"use client";
import React, { useEffect } from "react";
import { BaseFilter } from "@/components/base-filter";
import { Form, Select, DatePicker } from "antd";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getFactory } from "@/features/factory/factory-metadata/factory/actions";
import { IFactory } from "@/features/factory/factory-metadata/factory/types";
import dayjs from "dayjs";
import { rangePresets } from "@/constants/range-date";
import { useTranslations } from "next-intl";

const { RangePicker } = DatePicker;

interface ReceiveMaterialFilterProps {
  filterForm: any;
  handleFilter: (values: any) => void;
  handleResetFilter: () => void;
}

const ReceiveMaterialFilter = ({
  filterForm,
  handleFilter,
  handleResetFilter,
}: ReceiveMaterialFilterProps) => {
  const t = useTranslations("Factory.receive_material");
  const tc = useTranslations("Common");
  const tf = useTranslations("Factory.fg_receipt");

  useEffect(() => {
    filterForm.setFieldsValue({
      dateRange: [dayjs().subtract(1, "month"), dayjs()],
    });
  }, [filterForm]);

  const onFinish = (values: any) => {
    const { dateRange, ...rest } = values;
    const filterValues = {
      ...rest,
      start_date: dateRange?.[0]?.format("YYYY-MM-DD"),
      end_date: dateRange?.[1]?.format("YYYY-MM-DD"),
    };
    handleFilter(filterValues);
  };

  return (
    <BaseFilter
      form={filterForm}
      onFinish={onFinish}
      onReset={handleResetFilter}>
      <Form.Item name="status" label={t("status")}>
        <Select
          placeholder={t("status")}
          allowClear
          options={[
            { value: "arrived", label: t("arrived") },
            { value: "unloaded", label: t("unloaded") },
          ]}
        />
      </Form.Item>
      <Form.Item name="destination_factory_id" label={t("destination_factory")}>
        <InfiniteScrollSelect<IFactory>
          placeholder={t("destination_factory")}
          queryKey={["factories"]}
          fetchFn={getFactory}
          mapOption={(item) => ({
            label: item.factory_name,
            value: item.factory_id.toString(),
          })}
          allowClear
        />
      </Form.Item>
      <Form.Item
        name="dateRange"
        initialValue={[dayjs().subtract(1, "month"), dayjs()]}
        label={tf("created_date")}>
        <RangePicker
          placeholder={[tc("from_date"), tc("to_date")]}
          format="DD/MM/YYYY"
          presets={rangePresets}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default ReceiveMaterialFilter;
