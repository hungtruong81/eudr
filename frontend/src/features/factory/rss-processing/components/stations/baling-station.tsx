import { getProductTypes } from "@/features/factory/factory-metadata/product-type/action";
import { IProductType } from "@/features/factory/factory-metadata/product-type/types";
import { getFgReceiptSummary } from "@/features/factory/fg-receipt-summary/actions";
import { IFgReceiptSummary } from "@/features/factory/fg-receipt-summary/types";
import {
  addPalletItem,
  getPallet,
} from "@/features/sale/packing-price/pallet/actions";
import { IPallet } from "@/features/sale/packing-price/pallet/types";
import {
  Button,
  Card,
  Empty,
  Flex,
  Form,
  InputNumber,
  Space,
  Tag,
  Typography,
  message,
} from "antd";
import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  EditOutlined,
  FilterOutlined,
  GiftOutlined,
  PlusOutlined,
  QrcodeOutlined,
} from "@ant-design/icons";
import { QrScannerInput } from "../shared/qr-scanner-input";
import {
  BaleDraft,
  DriedPole,
  RSS_QUALITY_OPTIONS,
  RssQuality,
  RssWorkflowPlan,
  createWorkflowCode,
  getDriedPoleKey,
  getRssQualityLabel,
} from "../shared/rss-process-state";
import { getGrade } from "@/features/manage/grade/actions";
import { IGrade } from "@/features/manage/grade/type";
import { RssTechnicalDetails, RssTilePicker } from "../shared/rss-selection";

const { Text } = Typography;

interface BalingStationProps {
  workflowPlan?: RssWorkflowPlan | null;
  driedPoles: DriedPole[];
  baleDrafts: BaleDraft[];
  onCreateBaleDraft: (draft: BaleDraft) => void;
  onUpdateBaleDraft: (draft: BaleDraft) => void;
}

interface EmptyBaleFormValues {
  product_type_id: string;
  baleCount: number;
}

interface PackingFormValues {
  pallet_code: string;
  rubber_block_ids: number[];
}

export const BalingStation = ({
  workflowPlan,
  driedPoles,
  baleDrafts,
  onCreateBaleDraft,
  onUpdateBaleDraft,
}: BalingStationProps) => {
  const queryClient = useQueryClient();
  const [emptyBaleForm] = Form.useForm<EmptyBaleFormValues>();
  const [palletForm] = Form.useForm<PackingFormValues>();
  const [mode, setMode] = useState<"empty" | "press" | "pallet">("empty");
  const [selectedDraftCode, setSelectedDraftCode] = useState<string | null>(
    null,
  );
  const [selectedDraftGrade, setSelectedDraftGrade] =
    useState<RssQuality>("NA");
  const [selectedPoleKeys, setSelectedPoleKeys] = useState<string[]>([]);
  const [troughFilter, setTroughFilter] = useState<string>("all");

  const selectedProductTypeId = Form.useWatch(
    "product_type_id",
    emptyBaleForm,
  );
  const selectedPalletCode = Form.useWatch("pallet_code", palletForm);
  const selectedRubberBlockIds =
    Form.useWatch("rubber_block_ids", palletForm) ?? [];

  const { data: palletsData } = useQuery({
    queryKey: ["pallets-list"],
    queryFn: () => getPallet({ page: 1, limit: 100 }),
  });

  const { data: productTypesData } = useQuery({
    queryKey: ["product-types-baling"],
    queryFn: () => getProductTypes({ page: 1, limit: 100 }),
  });

  const { data: availableBlocksData } = useQuery({
    queryKey: ["available-rubber-blocks-station"],
    queryFn: () =>
      getFgReceiptSummary({ status: "available", page: 1, limit: 100 }),
  });

  const pallets = useMemo(
    () => palletsData?.data?.records ?? [],
    [palletsData],
  );
  const productTypes = useMemo(
    () => productTypesData?.data?.records ?? [],
    [productTypesData],
  );
  const availableBlocks = useMemo(
    () => availableBlocksData?.data?.records ?? [],
    [availableBlocksData],
  );

  useEffect(() => {
    if (!workflowPlan) return;
    setSelectedDraftGrade(workflowPlan.quality);
  }, [workflowPlan]);

  const driedPoleMap = useMemo(
    () =>
      new Map(driedPoles.map((pole) => [getDriedPoleKey(pole), pole] as const)),
    [driedPoles],
  );

  const selectedDraft = useMemo(
    () =>
      baleDrafts.find((draft) => draft.draftCode === selectedDraftCode) ?? null,
    [baleDrafts, selectedDraftCode],
  );

  const troughOptions = useMemo(() => {
    const troughCodes = Array.from(
      new Set(driedPoles.map((pole) => pole.troughCode)),
    );

    return [
      { label: "Tất cả mương", value: "all" },
      ...troughCodes.map((troughCode) => ({
        label: `Mương ${troughCode}`,
        value: troughCode,
      })),
    ];
  }, [driedPoles]);

  const productTypeOptions = useMemo(
    () =>
      productTypes.map((item: IProductType) => ({
        label: item.product_type_name,
        value: String(item.product_type_id),
        description: item.product_type_code,
        meta: item.product_weight ? `${item.product_weight} kg` : undefined,
      })),
    [productTypes],
  );

  const filteredDriedPoles = useMemo(
    () =>
      troughFilter === "all"
        ? driedPoles
        : driedPoles.filter((pole) => pole.troughCode === troughFilter),
    [driedPoles, troughFilter],
  );

  const driedPoleOptions = useMemo(
    () =>
      filteredDriedPoles.map((pole) => {
        const key = getDriedPoleKey(pole);

        return {
          label: `${pole.trolleyCode} / ${pole.poleCode}`,
          value: key,
          description: `Mương ${pole.troughCode} · ${pole.sheetCount} tờ`,
          meta: getRssQualityLabel(pole.grade),
        };
      }),
    [filteredDriedPoles],
  );

  const draftOptions = useMemo(
    () =>
      baleDrafts.map((draft) => ({
        label: draft.draftCode,
        value: draft.draftCode,
        description: `${draft.baleCount} bành · ${draft.sourcePoleIds.length} sào`,
        meta: getRssQualityLabel(draft.grade),
      })),
    [baleDrafts],
  );

  const palletOptions = useMemo(
    () =>
      pallets.map((pallet: IPallet) => ({
        label: pallet.pallet_code,
        value: pallet.pallet_code,
        description: `${pallet.total_bales || 0} bành`,
      })),
    [pallets],
  );

  const availableBlockOptions = useMemo(
    () =>
      availableBlocks.map((block: IFgReceiptSummary) => ({
        label: block.rubber_block_code?.toUpperCase() ?? "Bành chưa mã",
        value: block.rubber_block_id,
        description: `${block.weight} kg`,
        meta: block.grade || "NA",
      })),
    [availableBlocks],
  );

  const packPalletMutation = useMutation({
    mutationFn: async (values: PackingFormValues) => {
      return addPalletItem(values.pallet_code, {
        rubber_block_ids: values.rubber_block_ids.map(String),
      });
    },
    onSuccess: (_, values) => {
      message.success(
        `Đã đóng ${values.rubber_block_ids.length} bành vào pallet ${values.pallet_code}`,
      );
      queryClient.invalidateQueries({
        queryKey: ["available-rubber-blocks-station"],
      });
      queryClient.invalidateQueries({ queryKey: ["pallets-list"] });
      palletForm.resetFields();
    },
    onError: () => {
      message.error("Lỗi đóng gói pallet!");
    },
  });

  const { data: gradesData } = useQuery({
    queryKey: ["grades"],
    queryFn: () => getGrade({ page: 1, limit: 100 }),
  });

  const gradeOptions = useMemo(
    () =>
      gradesData?.data?.records?.map((item: IGrade) => ({
        label: `${item.name}`,
        value: item.grade_code,
        description: item.grade_code,
      })) ??
      RSS_QUALITY_OPTIONS.map((option) => ({
        label: option.label,
        value: option.value,
        description: option.desc,
      })),
    [gradesData],
  );

  const handleCreateEmptyBale = (values: EmptyBaleFormValues) => {
    const draft: BaleDraft = {
      draftCode: createWorkflowCode("BAL"),
      productTypeId: Number(values.product_type_id),
      baleCount: values.baleCount,
      grade: "NA",
      sourcePoleIds: [],
      createdAt: "Vừa tạo",
    };

    onCreateBaleDraft(draft);
    setSelectedDraftCode(draft.draftCode);
    setSelectedDraftGrade("NA");
    setSelectedPoleKeys([]);
    setMode("press");
    emptyBaleForm.resetFields();
    message.success(`Đã tạo ${values.baleCount} bành trống`);
  };

  const handleSelectDraft = (draftCode: string) => {
    const draft = baleDrafts.find((item) => item.draftCode === draftCode);

    setSelectedDraftCode(draftCode);
    setSelectedDraftGrade(draft?.grade ?? "NA");
    setSelectedPoleKeys(draft?.sourcePoleIds ?? []);
  };

  const handleScanDriedPole = (value: string) => {
    const normalized = value.trim().toLowerCase();
    const found = driedPoles.find((pole) => {
      const key = getDriedPoleKey(pole).toLowerCase();

      return (
        key === normalized ||
        pole.id.toLowerCase() === normalized ||
        `${pole.trolleyCode}/${pole.poleCode}`.toLowerCase() === normalized
      );
    });
    const key = found ? getDriedPoleKey(found) : value.trim();

    setSelectedPoleKeys((prev) => (prev.includes(key) ? prev : [...prev, key]));
    message.success(
      found
        ? `Đã chọn ${found.trolleyCode} / ${found.poleCode}`
        : `Đã chọn sào ${value}`,
    );
  };

  const handleAddPole = (key: string) => {
    setSelectedPoleKeys((prev) => (prev.includes(key) ? prev : [...prev, key]));
  };

  const handleSaveDraftPoles = () => {
    if (!selectedDraft) {
      message.error("Vui lòng chọn bành trống cần ép");
      return;
    }

    onUpdateBaleDraft({
      ...selectedDraft,
      grade: selectedDraftGrade,
      sourcePoleIds: selectedPoleKeys,
    });
    message.success(
      `Đã cập nhật ${selectedPoleKeys.length} sào vào ${selectedDraft.draftCode}`,
    );
  };

  const onFinishPacking = (values: PackingFormValues) => {
    packPalletMutation.mutate(values);
  };

  return (
    <Card
      className="rss-station-card"
      title={
        <Space>
          <GiftOutlined style={{ color: "#52c41a" }} />
          <span>Ép bành & đóng pallet</span>
        </Space>
      }>
      <Space orientation="vertical" style={{ width: "100%" }} size="large">
        <RssTilePicker<"empty" | "press" | "pallet">
          ariaLabel="Chọn thao tác ép bành"
          selectedValue={mode}
          options={[
            {
              value: "empty",
              label: (
                <Space>
                  <PlusOutlined />
                  <span>Tạo bành</span>
                </Space>
              ),
              description: "Tạo bành trống",
            },
            {
              value: "press",
              label: (
                <Space>
                  <EditOutlined />
                  <span>Ép bành</span>
                </Space>
              ),
              description: "Gắn sào vào bành",
            },
            {
              value: "pallet",
              label: (
                <Space>
                  <GiftOutlined />
                  <span>Đóng pallet</span>
                </Space>
              ),
              description: "Đưa bành lên pallet",
            },
          ]}
          onChange={(value) => setMode(value as "empty" | "press" | "pallet")}
        />

        {mode === "empty" ? (
          <Space orientation="vertical" style={{ width: "100%" }} size="large">
            <Form
              form={emptyBaleForm}
              layout="vertical"
              onFinish={handleCreateEmptyBale}
              size="large">
              <Form.Item
                name="product_type_id"
                rules={[
                  { required: true, message: "Vui lòng chọn loại bành" },
                ]}
                hidden>
                <input type="hidden" />
              </Form.Item>

              <Form.Item label="Chọn bành / quy chuẩn" required>
                <RssTilePicker
                  ariaLabel="Chọn loại bành"
                  selectedValue={selectedProductTypeId}
                  options={productTypeOptions}
                  emptyText="Chưa có loại bành"
                  onChange={(value) =>
                    emptyBaleForm.setFieldsValue({
                      product_type_id: value as string,
                    })
                  }
                />
              </Form.Item>

              <Form.Item
                name="baleCount"
                label="Số lượng bành trống cần tạo"
                initialValue={1}
                rules={[
                  { required: true, message: "Vui lòng nhập số lượng bành" },
                ]}>
                <InputNumber min={1} style={{ width: "100%" }} />
              </Form.Item>

              <Button
                type="primary"
                htmlType="submit"
                size="large"
                block
                className="rss-mobile-primary-action">
                Tạo bành trống
              </Button>
            </Form>

            <RssTechnicalDetails
              summary={`Danh sách bành trống (${baleDrafts.length})`}>
              <DraftList baleDrafts={baleDrafts} driedPoleMap={driedPoleMap} />
            </RssTechnicalDetails>
          </Space>
        ) : null}

        {mode === "press" ? (
          <Space orientation="vertical" style={{ width: "100%" }} size="large">
            <Card size="small" title="Chọn bành trống để ép">
              <RssTilePicker
                ariaLabel="Chọn bành trống để ép"
                selectedValue={selectedDraftCode}
                options={draftOptions}
                emptyText="Chưa có bành trống"
                onChange={(value) => handleSelectDraft(value as string)}
              />
            </Card>

            <Card size="small" title="Chọn grade cho bành">
              <RssTilePicker
                ariaLabel="Chọn grade cho bành"
                selectedValue={selectedDraftGrade}
                options={gradeOptions}
                onChange={(value) => setSelectedDraftGrade(value as RssQuality)}
              />
            </Card>

            <Card
              size="small"
              title={
                <Space>
                  <FilterOutlined />
                  <span>Tờ mủ lọc theo mương</span>
                </Space>
              }>
              <RssTilePicker
                ariaLabel="Lọc tờ mủ theo mương"
                selectedValue={troughFilter}
                options={troughOptions}
                onChange={(value) => setTroughFilter(value as string)}
              />
            </Card>

            <Card size="small" title="Edit sào vào bành">
              <Space
                orientation="vertical"
                style={{ width: "100%" }}
                size="middle">
                <QrScannerInput
                  placeholder="Quét sào đã sấy: GG-03:Sào 01…"
                  onScan={handleScanDriedPole}
                />

                <RssTilePicker
                  ariaLabel="Chọn sào để đưa vào bành"
                  multiple
                  selectedValues={selectedPoleKeys}
                  options={driedPoleOptions}
                  emptyText="Chưa có sào đã sấy theo mương lọc"
                  onChange={(values) => setSelectedPoleKeys(values as string[])}
                />

                {selectedPoleKeys.length > 0 ? (
                  <Flex wrap="wrap" gap="small">
                    {selectedPoleKeys.map((key) => {
                      const pole = driedPoleMap.get(key);

                      return (
                        <Tag
                          key={key}
                          color="orange"
                          closable
                          onClose={() =>
                            setSelectedPoleKeys((prev) =>
                              prev.filter((item) => item !== key),
                            )
                          }>
                          <QrcodeOutlined />{" "}
                          {pole
                            ? `${pole.trolleyCode} / ${pole.poleCode} · Mương ${pole.troughCode}`
                            : key}
                        </Tag>
                      );
                    })}
                  </Flex>
                ) : null}

                <Button
                  type="primary"
                  block
                  onClick={handleSaveDraftPoles}
                  className="rss-mobile-primary-action">
                  Lưu sào vào bành
                </Button>
              </Space>
            </Card>

            <RssTechnicalDetails
              summary={`Sào đã sấy theo mương (${filteredDriedPoles.length})`}>
              {filteredDriedPoles.length === 0 ? (
                <Empty
                  image={Empty.PRESENTED_IMAGE_SIMPLE}
                  description="Chưa có sào theo mương đã lọc"
                />
              ) : (
                <div className="rss-record-list">
                  {filteredDriedPoles.map((pole) => {
                    const key = getDriedPoleKey(pole);

                    return (
                      <div key={key} className="rss-touch-list-item">
                        <Flex
                          align="center"
                          justify="space-between"
                          gap="small"
                          wrap>
                          <Space orientation="vertical" size={0}>
                            <Text strong>
                              {pole.trolleyCode} / {pole.poleCode}
                            </Text>
                            <Text type="secondary">
                              Mương {pole.troughCode} · {pole.sheetCount} tờ ·
                              Lò {pole.ovenCode}
                            </Text>
                          </Space>
                          <Space>
                            <Tag color="green">
                              {getRssQualityLabel(pole.grade)}
                            </Tag>
                            <Button
                              size="small"
                              type="primary"
                              onClick={() => handleAddPole(key)}>
                              Chọn
                            </Button>
                          </Space>
                        </Flex>
                      </div>
                    );
                  })}
                </div>
              )}
            </RssTechnicalDetails>

            <RssTechnicalDetails
              summary={`Danh sách bành trống (${baleDrafts.length})`}>
              <DraftList baleDrafts={baleDrafts} driedPoleMap={driedPoleMap} />
            </RssTechnicalDetails>
          </Space>
        ) : null}

        {mode === "pallet" ? (
          <Form
            form={palletForm}
            layout="vertical"
            onFinish={onFinishPacking}
            size="large">
            <Space
              orientation="vertical"
              style={{ width: "100%" }}
              size="middle">
              <Form.Item
                name="pallet_code"
                rules={[
                  {
                    required: true,
                    message: "Vui lòng chọn pallet",
                  },
                ]}
                hidden>
                <input type="hidden" />
              </Form.Item>

              <Form.Item label="Chọn pallet đóng hàng" required>
                <RssTilePicker
                  ariaLabel="Chọn pallet đóng hàng"
                  selectedValue={selectedPalletCode}
                  options={palletOptions}
                  emptyText="Chưa có pallet"
                  onChange={(value) =>
                    palletForm.setFieldsValue({ pallet_code: value as string })
                  }
                />
              </Form.Item>

              <Form.Item
                name="rubber_block_ids"
                rules={[
                  { required: true, message: "Vui lòng chọn ít nhất một bành" },
                ]}
                hidden>
                <input type="hidden" />
              </Form.Item>

              <Form.Item label="Chọn các bành để đóng vào pallet" required>
                <RssTilePicker<number>
                  ariaLabel="Chọn các bành để đóng vào pallet"
                  multiple
                  selectedValues={selectedRubberBlockIds}
                  options={availableBlockOptions}
                  emptyText="Chưa có bành cao su rời"
                  onChange={(values) =>
                    palletForm.setFieldsValue({
                      rubber_block_ids: values as number[],
                    })
                  }
                />
              </Form.Item>

              <Button
                type="primary"
                htmlType="submit"
                size="large"
                block
                className="rss-mobile-primary-action"
                loading={packPalletMutation.isPending}>
                Xác nhận đóng pallet
              </Button>
            </Space>
          </Form>
        ) : null}
      </Space>
    </Card>
  );
};

const DraftList = ({
  baleDrafts,
  driedPoleMap,
}: {
  baleDrafts: BaleDraft[];
  driedPoleMap: Map<string, DriedPole>;
}) => (
  <Card size="small" title="Danh sách bành trống" style={{ marginTop: 16 }}>
    {baleDrafts.length === 0 ? (
      <Empty
        image={Empty.PRESENTED_IMAGE_SIMPLE}
        description="Chưa có bành trống"
      />
    ) : (
      <div className="rss-record-list">
        {baleDrafts.map((draft) => (
          <div key={draft.draftCode} className="rss-touch-list-item">
            <Flex align="center" justify="space-between" gap="small" wrap>
              <Space orientation="vertical" size={0}>
                <Text strong>{draft.draftCode}</Text>
                <Text type="secondary">
                  {draft.baleCount} bành · {draft.sourcePoleIds.length} sào ·{" "}
                  {draft.sourcePoleIds.length > 0
                    ? draft.sourcePoleIds
                        .map((key) => {
                          const pole = driedPoleMap.get(key);
                          return pole
                            ? `${pole.trolleyCode}/${pole.poleCode} từ mương ${pole.troughCode}`
                            : key;
                        })
                        .join(", ")
                    : "Bành trống"}
                </Text>
              </Space>
              <Space size={4} wrap>
                <Tag
                  color={draft.sourcePoleIds.length > 0 ? "green" : "default"}>
                  {draft.sourcePoleIds.length > 0 ? "Đã gán sào" : "Trống"}
                </Tag>
                <Tag color="blue">Grade {getRssQualityLabel(draft.grade)}</Tag>
              </Space>
            </Flex>
          </div>
        ))}
      </div>
    )}
  </Card>
);
