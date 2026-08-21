"use client";
import React from "react";
import { BaseFilter } from "@/components/base-filter";
import { Form, Select } from "antd";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { IFactory } from "../../factory/types";
import { getFactory } from "../../factory/actions";
import { useTranslations } from "next-intl";

interface ProductOvenFilterProps {
  filterForm: any;
  handleFilter: (values: any) => void;
  handleResetFilter: () => void;
}

export const ProductOvenFilter = ({
  filterForm,
  handleFilter,
  handleResetFilter,
}: ProductOvenFilterProps) => {
  const t = useTranslations("Factory.metadata.product_oven");
  const tm = useTranslations("Factory.metadata");

  const mapFactoryOptions = (item: IFactory) => ({
    value: item.factory_id.toString(),
    label: item.factory_name,
  });

  return (
    <BaseFilter
      form={filterForm}
      onFinish={handleFilter}
      onReset={handleResetFilter}>
      <Form.Item name="factory_id" label={t("factory")}>
        <InfiniteScrollSelect<IFactory>
          queryKey={["factories-filter"]}
          fetchFn={(p) => getFactory({ page: p.page, limit: p.limit })}
          mapOption={mapFactoryOptions}
          placeholder={tm("select_factory")}
          style={{ width: 250 }}
          allowClear
        />
      </Form.Item>

      <Form.Item name="status" label={t("status")}>
        <Select
          options={[
            { value: "available", label: t("status_available") },
            { value: "in_use", label: t("status_in_use") },
            { value: "cleaning", label: t("status_cleaning") },
          ]}
          placeholder={t("select_status")}
          style={{ width: 200 }}
          allowClear
        />
      </Form.Item>
    </BaseFilter>
  );
};
