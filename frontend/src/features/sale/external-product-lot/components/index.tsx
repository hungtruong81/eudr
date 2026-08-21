"use client";

import React, { useState } from "react";
import dayjs from "dayjs";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  Flex,
  Space,
  Row,
  Col,
  Spin,
  Empty,
  Pagination,
  message,
  Button,
} from "antd";
import { ImportOutlined, PlusOutlined } from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import {
  getExternalProductLots,
  deleteExternalProductLot,
  confirmExternalProductLot,
  cancelExternalProductLot,
  IGetExternalProductLotParams,
} from "../actions";
import ExternalProductLotFilter from "./external-product-lot-filter";
import ExternalProductLotCard from "./external-product-lot-card";
import ExternalProductLotForm from "./external-product-lot-form";
import ExternalProductLotDetail from "./external-product-lot-detail";
import ExternalProductLotImport from "./external-product-lot-import";
import { handleApiError } from "@/lib/api-error";
import { IProductLotExternal } from "../types";
import { useTranslations } from "next-intl";

const QUERY_KEY = "external-product-lots";

const ExternalProductLot = () => {
  const t = useTranslations("Sales.external_product_lot");
  const tCommon = useTranslations("Common");

  const queryClient = useQueryClient();
  const [params, setParams] = useState<Partial<IGetExternalProductLotParams>>({
    page: 1,
    limit: 12,
    status: "all",
    lot_type: "external",
    production_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
    production_date_to: dayjs().format("YYYY-MM-DD"),
  });

  const [openForm, setOpenForm] = useState(false);
  const [openImport, setOpenImport] = useState(false);
  const [selectedRecord, setSelectedRecord] =
    useState<IProductLotExternal | null>(null);
  const [openDetail, setOpenDetail] = useState(false);
  const [selectedId, setSelectedId] = useState<string | null>(null);

  const { data, isFetching, refetch } = useQuery({
    queryKey: [QUERY_KEY, params],
    queryFn: () =>
      getExternalProductLots(params as IGetExternalProductLotParams),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => deleteExternalProductLot(id),
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: [QUERY_KEY] });
    },
    onError: (error) => handleApiError(error),
  });

  const confirmMutation = useMutation({
    mutationFn: (id: string) => confirmExternalProductLot(id),
    onSuccess: () => {
      message.success(t("confirm_success"));
      queryClient.invalidateQueries({ queryKey: [QUERY_KEY] });
    },
    onError: (error) => handleApiError(error),
  });

  const cancelMutation = useMutation({
    mutationFn: (id: string) => cancelExternalProductLot(id),
    onSuccess: () => {
      message.success(t("cancel_success"));
      queryClient.invalidateQueries({ queryKey: [QUERY_KEY] });
    },
    onError: (error) => handleApiError(error),
  });

  const handleSearch = (newParams: Partial<IGetExternalProductLotParams>) => {
    setParams((prev) => ({
      ...prev,
      ...newParams,
      page: 1,
    }));
  };

  const handleEdit = (record: IProductLotExternal) => {
    setSelectedRecord(record);
    setOpenForm(true);
  };

  const handleAdd = () => {
    setSelectedRecord(null);
    setOpenForm(true);
  };

  const handleDelete = (record: IProductLotExternal) => {
    deleteMutation.mutate(record.product_lot_code);
  };

  const handleConfirm = (record: IProductLotExternal) => {
    confirmMutation.mutate(record.product_lot_code);
  };

  const handleCancel = (record: IProductLotExternal) => {
    cancelMutation.mutate(record.product_lot_code);
  };

  const handleView = (record: IProductLotExternal) => {
    setSelectedId(record.product_lot_code);
    setOpenDetail(true);
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex align="center" justify="space-between" wrap="wrap" gap="small">
        <ExternalProductLotFilter onSearch={handleSearch} />

        <Space>
          <Button
            icon={<PlusOutlined />}
            onClick={() => setOpenImport(true)}
            type="primary">
            {tCommon("add_new")}
          </Button>
          {/* <TooltipButton
            tooltip={t("add_new_tooltip")}
            icon={<PlusOutlined />}
            type="primary"
            onClick={handleAdd}>
            {tCommon("add_new")}
          </TooltipButton> */}
        </Space>
      </Flex>

      <Spin spinning={isFetching}>
        {(!data?.data?.records || data.data.records.length === 0) &&
        !isFetching ? (
          <Empty description={t("empty_list")} />
        ) : (
          <Flex vertical gap="large">
            <Row gutter={[16, 16]}>
              {data?.data?.records?.map((record: any) => (
                <Col
                  xs={24}
                  sm={24}
                  md={12}
                  lg={8}
                  xl={6}
                  key={record.product_lot_id}>
                  <ExternalProductLotCard
                    record={record}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    onView={handleView}
                    onConfirm={handleConfirm}
                    onCancel={handleCancel}
                    deleting={
                      deleteMutation.isPending &&
                      deleteMutation.variables === record.product_lot_code
                    }
                    confirming={
                      confirmMutation.isPending &&
                      confirmMutation.variables === record.product_lot_code
                    }
                    cancelling={
                      cancelMutation.isPending &&
                      cancelMutation.variables === record.product_lot_code
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

      <ExternalProductLotForm
        open={openForm}
        onClose={() => setOpenForm(false)}
        record={selectedRecord}
        onSuccess={refetch}
      />

      <ExternalProductLotImport
        open={openImport}
        onClose={() => setOpenImport(false)}
        onSuccess={refetch}
      />

      <ExternalProductLotDetail
        open={openDetail}
        onClose={() => {
          setOpenDetail(false);
          setSelectedId(null);
        }}
        productLotId={selectedId}
      />
    </Space>
  );
};

export default ExternalProductLot;
