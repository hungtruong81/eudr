"use client";

import React, { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useTranslations } from "next-intl";
import dayjs from "dayjs";

import {
  inventoryProductLots,
  IGetProductLotInventoryParams,
} from "@/features/factory/lot/actions";
import InventoryFilter from "./inventory-filter";
import InventoryTable from "./inventory-table";
import InventoryStats from "./inventory-stats";
import { Card, CardContent } from "@/components/ui/card";

const TraderInventory = () => {
  const t = useTranslations("Inventory");
  const tCommon = useTranslations("Common");

  const [params, setParams] = useState<Partial<IGetProductLotInventoryParams>>({
    page: 1,
    limit: 10,
    eudr_type: "all",
    lot_type: "all",
    production_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
    production_date_to: dayjs().format("YYYY-MM-DD"),
  });

  const { data, isFetching } = useQuery({
    queryKey: ["trader-inventory", params],
    queryFn: () =>
      inventoryProductLots(params as IGetProductLotInventoryParams),
  });

  const handleSearch = (newParams: Partial<IGetProductLotInventoryParams>) => {
    setParams((prev) => ({
      ...prev,
      ...newParams,
      page: 1,
    }));
  };

  const handlePageChange = (page: number, limit: number) => {
    setParams((prev) => ({
      ...prev,
      page,
      limit,
    }));
  };

  return (
    <div className="flex flex-col gap-6 ">
      {/* <InventoryStats 
        data={data?.data?.records || []} 
        totalRecords={data?.data?.total_records}
        loading={isFetching}
      /> */}

      <InventoryFilter onSearch={handleSearch} />

      <InventoryTable
        data={data?.data?.records || []}
        loading={isFetching}
        pagination={{
          current: Number(data?.data?.current_page) || 1,
          pageSize: Number(data?.data?.page_limit) || 10,
          total: Number(data?.data?.total_records) || 0,
        }}
        onPageChange={handlePageChange}
      />
    </div>
  );
};

export default TraderInventory;
