"use client";
import React, { useEffect } from "react";
import { BaseFilter } from "@/components/base-filter";
import { Form, Input, Select, DatePicker } from "antd";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getFactory } from "@/features/factory/factory-metadata/factory/actions";
import { IFactory } from "@/features/factory/factory-metadata/factory/types";
import dayjs from "dayjs";
import { rangePresets } from "@/constants/range-date";

const { RangePicker } = DatePicker;

import { useTranslations } from "next-intl";

interface TransportationRouteFilterProps {
  filterForm: any;
  handleFilter: (values: any) => void;
  handleResetFilter: () => void;
}

export const TransportationRouteFilter = ({
  filterForm,
  handleFilter,
  handleResetFilter,
}: TransportationRouteFilterProps) => {
  const tCommon = useTranslations("Common");
  const tRoute = useTranslations("Route.transportation");

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
      <Form.Item name="search" label={tCommon("search")}>
        <Input placeholder={tRoute("filter_search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item name="status" label={tRoute("status")}>
        <Select
          placeholder={tRoute("status")}
          allowClear
          options={[
            { value: "pending", label: tRoute("status_pending") },
            { value: "unloaded", label: tRoute("status_unloaded") },
            { value: "arrived", label: tRoute("status_arrived") },
            { value: "cancelled", label: tRoute("status_cancelled") },
          ]}
        />
      </Form.Item>
      <Form.Item name="destination_factory_id" label={tRoute("destination_factory")}>
        <InfiniteScrollSelect<IFactory>
          placeholder={tRoute("destination_factory")}
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
        label={tCommon("creation_date")}>
        <RangePicker
          placeholder={[tCommon("from_date"), tCommon("to_date")]}
          format="DD/MM/YYYY"
          presets={rangePresets}
        />
      </Form.Item>
    </BaseFilter>
  );
};
