"use client";
import React, { useState } from "react";
import dayjs from "dayjs";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Space, Row, Col, Spin, Empty, Pagination, message } from "antd";
import { PlusOutlined } from "@ant-design/icons";
import { useTranslations } from "next-intl";
import { TooltipButton } from "@/components/tooltip-button";
import {
  getProductLots,
  deleteProductLot,
  IGetProductLotParams,
} from "../actions";
import { IProductLot } from "../types";
import ProductLotFilter from "./product-lot-filter";
import ProductLotCard from "./product-lot-card";
import ProductLotForm from "./product-lot-form";
import ProductLotDetail from "./product-lot-detail";
import { handleApiError } from "@/lib/api-error";

const ProductLot = () => {
  const queryClient = useQueryClient();
  const [params, setParams] = useState<Partial<IGetProductLotParams>>({
    page: 1,
    limit: 12,
    status: "all",
    lot_type: "all",
    production_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
    production_date_to: dayjs().format("YYYY-MM-DD"),
  });

  const [openForm, setOpenForm] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<IProductLot | null>(
    null,
  );
  const [openDetail, setOpenDetail] = useState(false);
  const [selectedCode, setSelectedCode] = useState<string | null>(null);

  const { data, isFetching, refetch } = useQuery({
    queryKey: ["product-lots", params],
    queryFn: () => getProductLots(params as IGetProductLotParams),
  });

  const t = useTranslations("Factory");
  const tc = useTranslations("Common");

  const deleteMutation = useMutation({
    mutationFn: (code: string) => deleteProductLot(code),
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["product-lots"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleSearch = (newParams: Partial<IGetProductLotParams>) => {
    setParams((prev) => ({
      ...prev,
      ...newParams,
      page: 1,
    }));
  };

  const handleEdit = (record: IProductLot) => {
    setSelectedRecord(record);
    setOpenForm(true);
  };

  const handleAdd = () => {
    setSelectedRecord(null);
    setOpenForm(true);
  };

  const handleDelete = (record: IProductLot) => {
    deleteMutation.mutate(record.product_lot_code);
  };

  const handleView = (record: IProductLot) => {
    setSelectedCode(record.product_lot_code);
    setOpenDetail(true);
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex align="center" justify="space-between">
        <ProductLotFilter onSearch={handleSearch} />

        <TooltipButton
          tooltip={t("add_lot")}
          icon={<PlusOutlined />}
          type="primary"
          onClick={handleAdd}>
          {tc("add")}
        </TooltipButton>
      </Flex>

      <Spin spinning={isFetching}>
        {(!data?.data?.records || data.data.records.length === 0) &&
        !isFetching ? (
          <Empty description={t("no_lots")} />
        ) : (
          <Flex vertical gap="large">
            <Row gutter={[16, 16]}>
              {data?.data?.records?.map((record) => (
                <Col
                  xs={24}
                  sm={24}
                  md={12}
                  lg={8}
                  xl={6}
                  key={record.product_lot_id}>
                  <ProductLotCard
                    record={record}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    onView={handleView}
                    deleting={
                      deleteMutation.isPending &&
                      deleteMutation.variables === record.product_lot_code
                    }
                  />
                </Col>
              ))}
            </Row>

            {data && data.data.total_records > 0 && (
              <Flex justify="flex-end">
                <Pagination
                  current={+data.data.current_page}
                  total={+data.data.total_records}
                  pageSize={+data.data.page_limit}
                  showSizeChanger
                  onChange={(page, limit) => {
                    setParams((prev) => ({
                      ...prev,
                      page,
                      limit,
                    }));
                  }}
                />
              </Flex>
            )}
          </Flex>
        )}
      </Spin>

      <ProductLotForm
        open={openForm}
        onClose={() => setOpenForm(false)}
        record={selectedRecord}
        onSuccess={refetch}
      />

      <ProductLotDetail
        open={openDetail}
        onClose={() => {
          setOpenDetail(false);
          setSelectedCode(null);
        }}
        productLotCode={selectedCode}
      />
    </Space>
  );
};

export default ProductLot;
