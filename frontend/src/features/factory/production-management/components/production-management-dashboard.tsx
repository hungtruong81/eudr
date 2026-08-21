"use client";

import {
  App,
  Badge,
  Button,
  Card,
  Col,
  Drawer,
  Empty,
  Flex,
  Form,
  Grid,
  Input,
  InputNumber,
  Progress,
  Row,
  Select,
  Space,
  Statistic,
  Table,
  Tabs,
  Tag,
  Tooltip,
  Typography,
} from "antd";
import type { TableColumnsType } from "antd";
import {
  BarcodeOutlined,
  CheckCircleOutlined,
  CompressOutlined,
  DatabaseOutlined,
  ExperimentOutlined,
  EyeOutlined,
  FilterOutlined,
  FireOutlined,
  GiftOutlined,
  InboxOutlined,
  PlusOutlined,
  ReloadOutlined,
  SearchOutlined,
} from "@ant-design/icons";
import { useCallback, useMemo, useState } from "react";
import type { ReactNode } from "react";

const { Text, Title } = Typography;
const { useBreakpoint } = Grid;

type StageKey =
  | "tanks"
  | "troughs"
  | "rolledSheets"
  | "airDried"
  | "ovenDried"
  | "bales"
  | "pallets";

type QualityGrade = "RSS 1" | "RSS 2" | "RSS 3" | "Mixed";

type ProductionStatus =
  | "stable"
  | "attention"
  | "processing"
  | "ready"
  | "blocked"
  | "completed"
  | "packed";

type ProductionRecord = {
  id: string;
  code: string;
  name: string;
  stage: StageKey;
  source: string;
  location: string;
  quality: QualityGrade;
  status: ProductionStatus;
  updatedAt: string;
  availableSheets?: number;
  baleCount?: number;
  capacityKg?: number;
  dryDays?: number;
  drc?: number;
  moisture?: number;
  ph?: number;
  progress?: number;
  rackCount?: number;
  sheetCount?: number;
  volumeKg?: number;
  weightKg?: number;
  note?: string;
};

type StageMeta = {
  key: StageKey;
  label: string;
  shortLabel: string;
  description: string;
  icon: ReactNode;
};

type DetailRow = {
  label: string;
  value: ReactNode;
};

type PressingFormValues = {
  sourceBatch: string;
  sheetCount: number;
  grade: QualityGrade;
  pressMachine: string;
  baleCode?: string;
};

type PalletFormValues = {
  baleCodes: string[];
  warehouse: string;
  palletCode?: string;
};

const numberFormatter = new Intl.NumberFormat("vi-VN", {
  maximumFractionDigits: 1,
});

const STAGES: StageMeta[] = [
  {
    key: "tanks",
    label: "Bồn nguyên liệu",
    shortLabel: "Bồn",
    description: "Khối lượng và chất lượng mủ trong từng bồn.",
    icon: <DatabaseOutlined />,
  },
  {
    key: "troughs",
    label: "Mương",
    shortLabel: "Mương",
    description: "Theo dõi mương đang nhận mủ và mức ổn định chất lượng.",
    icon: <ExperimentOutlined />,
  },
  {
    key: "rolledSheets",
    label: "Tờ sau cán",
    shortLabel: "Sau cán",
    description: "Lô tờ sau khi cán theo từng mương.",
    icon: <BarcodeOutlined />,
  },
  {
    key: "airDried",
    label: "Tờ phơi khô",
    shortLabel: "Phơi",
    description: "Tờ trên sào phơi và độ ẩm còn lại.",
    icon: <InboxOutlined />,
  },
  {
    key: "ovenDried",
    label: "Tờ sau sấy",
    shortLabel: "Sấy",
    description: "Tờ đã ra khỏi máy sấy, sẵn sàng ép bành.",
    icon: <FireOutlined />,
  },
  {
    key: "bales",
    label: "Ép bành",
    shortLabel: "Ép bành",
    description: "Tạo bành từ đầu ra máy sấy và số tờ cần ép.",
    icon: <CompressOutlined />,
  },
  {
    key: "pallets",
    label: "Đóng pallet",
    shortLabel: "Pallet",
    description: "Gom bành đã ép thành pallet thành phẩm.",
    icon: <GiftOutlined />,
  },
];

const STATUS_META: Record<ProductionStatus, { color: string; label: string }> =
  {
    stable: { color: "green", label: "Ổn định" },
    attention: { color: "gold", label: "Cần theo dõi" },
    processing: { color: "blue", label: "Đang xử lý" },
    ready: { color: "cyan", label: "Sẵn sàng" },
    blocked: { color: "red", label: "Chờ xử lý" },
    completed: { color: "purple", label: "Hoàn tất" },
    packed: { color: "geekblue", label: "Đã đóng" },
  };

const QUALITY_COLOR: Record<QualityGrade, string> = {
  "RSS 1": "green",
  "RSS 2": "blue",
  "RSS 3": "gold",
  Mixed: "purple",
};

const INITIAL_STAGE_RECORDS: Record<StageKey, ProductionRecord[]> = {
  tanks: [
    {
      id: "tank-1",
      code: "RAW-T01",
      name: "Bồn 1",
      stage: "tanks",
      source: "Chuyến RM-0526-01",
      location: "Khu nguyên liệu A",
      quality: "RSS 2",
      status: "stable",
      updatedAt: "30/05/2026 08:10",
      volumeKg: 12800,
      capacityKg: 15000,
      drc: 31.4,
      ph: 6.8,
      progress: 85,
      note: "Độ khô ổn định, đủ điều kiện xả qua mương.",
    },
    {
      id: "tank-2",
      code: "RAW-T02",
      name: "Bồn 2",
      stage: "tanks",
      source: "Chuyến RM-0526-02",
      location: "Khu nguyên liệu A",
      quality: "RSS 1",
      status: "ready",
      updatedAt: "30/05/2026 08:35",
      volumeKg: 9400,
      capacityKg: 12000,
      drc: 33.1,
      ph: 6.9,
      progress: 78,
    },
    {
      id: "tank-3",
      code: "RAW-T03",
      name: "Bồn 3",
      stage: "tanks",
      source: "Chuyến RM-0525-07",
      location: "Khu nguyên liệu B",
      quality: "RSS 3",
      status: "attention",
      updatedAt: "30/05/2026 07:55",
      volumeKg: 6100,
      capacityKg: 10000,
      drc: 29.8,
      ph: 6.5,
      progress: 61,
      note: "Cần kiểm tra lại chỉ số pH trước khi đưa vào mương.",
    },
  ],
  troughs: [
    {
      id: "trough-1",
      code: "TR-01",
      name: "Mương 1",
      stage: "troughs",
      source: "RAW-T01",
      location: "Dãy mương A",
      quality: "RSS 2",
      status: "processing",
      updatedAt: "30/05/2026 09:05",
      sheetCount: 420,
      weightKg: 690,
      progress: 72,
    },
    {
      id: "trough-2",
      code: "TR-02",
      name: "Mương 2",
      stage: "troughs",
      source: "RAW-T02",
      location: "Dãy mương A",
      quality: "RSS 1",
      status: "stable",
      updatedAt: "30/05/2026 09:12",
      sheetCount: 380,
      weightKg: 625,
      progress: 64,
    },
    {
      id: "trough-3",
      code: "TR-06",
      name: "Mương 6",
      stage: "troughs",
      source: "RAW-T03",
      location: "Dãy mương B",
      quality: "RSS 3",
      status: "attention",
      updatedAt: "30/05/2026 08:42",
      sheetCount: 260,
      weightKg: 430,
      progress: 48,
    },
  ],
  rolledSheets: [
    {
      id: "rolled-1",
      code: "RS-3005-001",
      name: "Lô tờ cán 001",
      stage: "rolledSheets",
      source: "TR-01",
      location: "Khu cán 1",
      quality: "RSS 2",
      status: "ready",
      updatedAt: "30/05/2026 10:20",
      sheetCount: 410,
      weightKg: 582,
      moisture: 47.8,
    },
    {
      id: "rolled-2",
      code: "RS-3005-002",
      name: "Lô tờ cán 002",
      stage: "rolledSheets",
      source: "TR-02",
      location: "Khu cán 1",
      quality: "RSS 1",
      status: "ready",
      updatedAt: "30/05/2026 10:38",
      sheetCount: 364,
      weightKg: 528,
      moisture: 46.2,
    },
    {
      id: "rolled-3",
      code: "RS-3005-003",
      name: "Lô tờ cán 003",
      stage: "rolledSheets",
      source: "TR-06",
      location: "Khu cán 2",
      quality: "RSS 3",
      status: "blocked",
      updatedAt: "30/05/2026 10:44",
      sheetCount: 248,
      weightKg: 356,
      moisture: 50.4,
      note: "Giữ lại để phân loại trước khi đưa lên sào phơi.",
    },
  ],
  airDried: [
    {
      id: "air-1",
      code: "AD-3005-011",
      name: "Sào phơi 11",
      stage: "airDried",
      source: "RS-3005-001",
      location: "Nhà phơi A",
      quality: "RSS 2",
      status: "processing",
      updatedAt: "30/05/2026 11:05",
      sheetCount: 398,
      rackCount: 12,
      dryDays: 2,
      moisture: 24.5,
      progress: 62,
    },
    {
      id: "air-2",
      code: "AD-3005-012",
      name: "Sào phơi 12",
      stage: "airDried",
      source: "RS-3005-002",
      location: "Nhà phơi A",
      quality: "RSS 1",
      status: "ready",
      updatedAt: "30/05/2026 11:30",
      sheetCount: 352,
      rackCount: 10,
      dryDays: 3,
      moisture: 18.7,
      progress: 100,
    },
    {
      id: "air-3",
      code: "AD-3005-018",
      name: "Sào phơi 18",
      stage: "airDried",
      source: "RS-3005-003",
      location: "Nhà phơi B",
      quality: "RSS 3",
      status: "attention",
      updatedAt: "30/05/2026 11:40",
      sheetCount: 236,
      rackCount: 8,
      dryDays: 1,
      moisture: 31.2,
      progress: 36,
    },
  ],
  ovenDried: [
    {
      id: "oven-1",
      code: "OD-3005-021",
      name: "Mẻ sấy 21",
      stage: "ovenDried",
      source: "Máy sấy 1 / AD-3005-012",
      location: "Buồng sấy 1",
      quality: "RSS 1",
      status: "ready",
      updatedAt: "30/05/2026 13:20",
      sheetCount: 340,
      availableSheets: 340,
      weightKg: 486,
      moisture: 3.8,
      progress: 100,
    },
    {
      id: "oven-2",
      code: "OD-3005-022",
      name: "Mẻ sấy 22",
      stage: "ovenDried",
      source: "Máy sấy 2 / AD-3005-011",
      location: "Buồng sấy 2",
      quality: "RSS 2",
      status: "processing",
      updatedAt: "30/05/2026 13:35",
      sheetCount: 260,
      availableSheets: 180,
      weightKg: 372,
      moisture: 5.4,
      progress: 74,
    },
    {
      id: "oven-3",
      code: "OD-3005-023",
      name: "Mẻ sấy 23",
      stage: "ovenDried",
      source: "Máy sấy 3 / AD-3005-018",
      location: "Buồng sấy 3",
      quality: "RSS 3",
      status: "attention",
      updatedAt: "30/05/2026 14:00",
      sheetCount: 215,
      availableSheets: 120,
      weightKg: 301,
      moisture: 7.6,
      progress: 58,
    },
  ],
  bales: [
    {
      id: "bale-1",
      code: "BAL-3005-001",
      name: "Bành RSS 1 - 001",
      stage: "bales",
      source: "OD-3005-021",
      location: "Máy ép 1",
      quality: "RSS 1",
      status: "ready",
      updatedAt: "30/05/2026 14:25",
      sheetCount: 36,
      weightKg: 50,
      progress: 100,
    },
    {
      id: "bale-2",
      code: "BAL-3005-002",
      name: "Bành RSS 2 - 002",
      stage: "bales",
      source: "OD-3005-022",
      location: "Máy ép 2",
      quality: "RSS 2",
      status: "ready",
      updatedAt: "30/05/2026 14:45",
      sheetCount: 34,
      weightKg: 48,
      progress: 100,
    },
    {
      id: "bale-3",
      code: "BAL-3005-003",
      name: "Bành RSS 3 - 003",
      stage: "bales",
      source: "OD-3005-023",
      location: "Máy ép 2",
      quality: "RSS 3",
      status: "completed",
      updatedAt: "30/05/2026 15:05",
      sheetCount: 32,
      weightKg: 45,
      progress: 100,
    },
  ],
  pallets: [
    {
      id: "pallet-1",
      code: "PAL-3005-001",
      name: "Pallet RSS 1 - 001",
      stage: "pallets",
      source: "24 bành",
      location: "Kho thành phẩm A",
      quality: "RSS 1",
      status: "packed",
      updatedAt: "30/05/2026 16:10",
      baleCount: 24,
      weightKg: 1200,
      progress: 100,
    },
    {
      id: "pallet-2",
      code: "PAL-3005-002",
      name: "Pallet RSS 2 - 002",
      stage: "pallets",
      source: "18 bành",
      location: "Kho thành phẩm B",
      quality: "RSS 2",
      status: "processing",
      updatedAt: "30/05/2026 16:24",
      baleCount: 18,
      weightKg: 870,
      progress: 75,
    },
  ],
};

const formatKg = (value?: number) => `${numberFormatter.format(value ?? 0)} kg`;

const formatSheets = (value?: number) =>
  `${numberFormatter.format(value ?? 0)} tờ`;

const formatPercent = (value?: number) =>
  value === undefined ? "-" : `${numberFormatter.format(value)}%`;

const renderQualityTag = (quality: QualityGrade) => (
  <Tag color={QUALITY_COLOR[quality]}>{quality}</Tag>
);

const renderStatusTag = (status: ProductionStatus) => {
  const meta = STATUS_META[status];
  return <Tag color={meta.color}>{meta.label}</Tag>;
};

const getRecordSearchText = (record: ProductionRecord) =>
  Object.values(record)
    .filter((value) => value !== undefined && value !== null)
    .map((value) => String(value).toLowerCase())
    .join(" ");

const createCode = (prefix: string) =>
  `${prefix}-${Date.now().toString().slice(-6)}`;

const buildDetailRows = (record: ProductionRecord): DetailRow[] => {
  const rows: DetailRow[] = [
    { label: "Mã", value: record.code },
    { label: "Tên", value: record.name },
    { label: "Nguồn", value: record.source },
    { label: "Vị trí", value: record.location },
    { label: "Chất lượng", value: renderQualityTag(record.quality) },
    { label: "Trạng thái", value: renderStatusTag(record.status) },
    { label: "Cập nhật", value: record.updatedAt },
  ];

  if (record.volumeKg !== undefined || record.capacityKg !== undefined) {
    rows.push({
      label: "Khối lượng / sức chứa",
      value: `${formatKg(record.volumeKg)} / ${formatKg(record.capacityKg)}`,
    });
  }

  if (record.weightKg !== undefined) {
    rows.push({ label: "Khối lượng", value: formatKg(record.weightKg) });
  }

  if (record.sheetCount !== undefined) {
    rows.push({ label: "Số tờ", value: formatSheets(record.sheetCount) });
  }

  if (record.availableSheets !== undefined) {
    rows.push({
      label: "Tờ có thể ép",
      value: formatSheets(record.availableSheets),
    });
  }

  if (record.baleCount !== undefined) {
    rows.push({ label: "Số bành", value: `${record.baleCount} bành` });
  }

  if (record.drc !== undefined) {
    rows.push({ label: "DRC", value: formatPercent(record.drc) });
  }

  if (record.ph !== undefined) {
    rows.push({ label: "pH", value: numberFormatter.format(record.ph) });
  }

  if (record.moisture !== undefined) {
    rows.push({ label: "Độ ẩm", value: formatPercent(record.moisture) });
  }

  if (record.dryDays !== undefined) {
    rows.push({ label: "Số ngày phơi", value: `${record.dryDays} ngày` });
  }

  if (record.rackCount !== undefined) {
    rows.push({ label: "Số sào", value: `${record.rackCount} sào` });
  }

  if (record.note) {
    rows.push({ label: "Ghi chú", value: record.note });
  }

  return rows;
};

export default function ProductionManagementDashboard() {
  const screens = useBreakpoint();
  const isMobile = !screens.md;
  const { message } = App.useApp();
  const [pressForm] = Form.useForm<PressingFormValues>();
  const [palletForm] = Form.useForm<PalletFormValues>();

  const [activeStage, setActiveStage] = useState<StageKey>("tanks");
  const [query, setQuery] = useState("");
  const [qualityFilter, setQualityFilter] = useState<QualityGrade>();
  const [statusFilter, setStatusFilter] = useState<ProductionStatus>();
  const [detailRecord, setDetailRecord] = useState<ProductionRecord | null>(
    null,
  );
  const [createdBales, setCreatedBales] = useState<ProductionRecord[]>([]);
  const [createdPallets, setCreatedPallets] = useState<ProductionRecord[]>([]);

  const selectedBaleCodes = Form.useWatch("baleCodes", palletForm);

  const recordsByStage = useMemo<Record<StageKey, ProductionRecord[]>>(
    () => ({
      ...INITIAL_STAGE_RECORDS,
      bales: [...INITIAL_STAGE_RECORDS.bales, ...createdBales],
      pallets: [...INITIAL_STAGE_RECORDS.pallets, ...createdPallets],
    }),
    [createdBales, createdPallets],
  );

  const activeMeta = useMemo(
    () => STAGES.find((stage) => stage.key === activeStage) ?? STAGES[0],
    [activeStage],
  );

  const currentRows = recordsByStage[activeStage];

  const qualityOptions = useMemo(
    () =>
      Array.from(new Set(currentRows.map((row) => row.quality))).map(
        (quality) => ({
          label: quality,
          value: quality,
        }),
      ),
    [currentRows],
  );

  const statusOptions = useMemo(
    () =>
      Array.from(new Set(currentRows.map((row) => row.status))).map(
        (status) => ({
          label: STATUS_META[status].label,
          value: status,
        }),
      ),
    [currentRows],
  );

  const filteredRows = useMemo(() => {
    const normalizedQuery = query.trim().toLowerCase();

    return currentRows.filter((record) => {
      const matchesQuery =
        normalizedQuery.length === 0 ||
        getRecordSearchText(record).includes(normalizedQuery);
      const matchesQuality =
        qualityFilter === undefined || record.quality === qualityFilter;
      const matchesStatus =
        statusFilter === undefined || record.status === statusFilter;

      return matchesQuery && matchesQuality && matchesStatus;
    });
  }, [currentRows, query, qualityFilter, statusFilter]);

  const allBales = recordsByStage.bales;

  const selectedBales = useMemo(() => {
    const codes = selectedBaleCodes ?? [];
    return allBales.filter((bale) => codes.includes(bale.code));
  }, [allBales, selectedBaleCodes]);

  const selectedBaleWeight = useMemo(
    () =>
      selectedBales.reduce((total, bale) => total + (bale.weightKg ?? 0), 0),
    [selectedBales],
  );

  const summaryItems = useMemo(
    () => [
      {
        title: "Nguyên liệu trong bồn",
        value: formatKg(
          recordsByStage.tanks.reduce(
            (total, row) => total + (row.volumeKg ?? 0),
            0,
          ),
        ),
        icon: <DatabaseOutlined />,
      },
      {
        title: "Tờ sẵn sàng sau sấy",
        value: formatSheets(
          recordsByStage.ovenDried.reduce(
            (total, row) => total + (row.availableSheets ?? 0),
            0,
          ),
        ),
        icon: <FireOutlined />,
      },
      {
        title: "Bành chờ pallet",
        value: recordsByStage.bales.filter((row) => row.status === "ready")
          .length,
        icon: <CompressOutlined />,
      },
      {
        title: "Pallet đã đóng",
        value: recordsByStage.pallets.filter((row) => row.status === "packed")
          .length,
        icon: <GiftOutlined />,
      },
    ],
    [recordsByStage],
  );

  const handleResetFilters = useCallback(() => {
    setQuery("");
    setQualityFilter(undefined);
    setStatusFilter(undefined);
  }, []);

  const handleCreateBale = useCallback(
    (values: PressingFormValues) => {
      const source = recordsByStage.ovenDried.find(
        (record) => record.code === values.sourceBatch,
      );

      if (!source) {
        message.error("Không tìm thấy mẻ sấy đã chọn");
        return;
      }

      const availableSheets = source.availableSheets ?? source.sheetCount ?? 0;
      if (values.sheetCount > availableSheets) {
        message.error("Số tờ ép bành vượt quá số tờ có thể lấy ra");
        return;
      }

      const code = values.baleCode?.trim() || createCode("BAL");
      const newBale: ProductionRecord = {
        id: `created-${code}`,
        code,
        name: `Bành ${values.grade} - ${code}`,
        stage: "bales",
        source: source.code,
        location: values.pressMachine,
        quality: values.grade,
        status: "ready",
        updatedAt: "Vừa tạo",
        sheetCount: values.sheetCount,
        weightKg: Math.round(values.sheetCount * 1.38),
        progress: 100,
        note: `Ép từ ${source.name}`,
      };

      setCreatedBales((prev) => [newBale, ...prev]);
      setActiveStage("bales");
      pressForm.resetFields();
      message.success("Đã tạo bành mới");
    },
    [message, pressForm, recordsByStage.ovenDried],
  );

  const handleCreatePallet = useCallback(
    (values: PalletFormValues) => {
      const packedBales = allBales.filter((bale) =>
        values.baleCodes.includes(bale.code),
      );

      if (packedBales.length === 0) {
        message.error("Vui lòng chọn ít nhất một bành");
        return;
      }

      const code = values.palletCode?.trim() || createCode("PAL");
      const totalWeight = packedBales.reduce(
        (total, bale) => total + (bale.weightKg ?? 0),
        0,
      );
      const quality = packedBales[0]?.quality ?? "Mixed";
      const newPallet: ProductionRecord = {
        id: `created-${code}`,
        code,
        name: `Pallet ${quality} - ${code}`,
        stage: "pallets",
        source: `${packedBales.length} bành`,
        location: values.warehouse,
        quality,
        status: "packed",
        updatedAt: "Vừa tạo",
        baleCount: packedBales.length,
        weightKg: totalWeight,
        progress: 100,
      };

      setCreatedPallets((prev) => [newPallet, ...prev]);
      setActiveStage("pallets");
      palletForm.resetFields();
      message.success("Đã đóng pallet mới");
    },
    [allBales, message, palletForm],
  );

  const handlePreparePress = useCallback(
    (record: ProductionRecord) => {
      setActiveStage("bales");
      pressForm.setFieldsValue({
        sourceBatch: record.code,
        grade: record.quality,
        pressMachine: "Máy ép 1",
        sheetCount: Math.min(
          record.availableSheets ?? record.sheetCount ?? 0,
          36,
        ),
      });
    },
    [pressForm],
  );

  const handlePreparePallet = useCallback(
    (record: ProductionRecord) => {
      setActiveStage("pallets");
      palletForm.setFieldsValue({
        baleCodes: [record.code],
        warehouse: "Kho thành phẩm A",
      });
    },
    [palletForm],
  );

  const detailColumns = useMemo<TableColumnsType<DetailRow>>(
    () => [
      {
        title: "Thông tin",
        dataIndex: "label",
        key: "label",
        width: "38%",
        render: (value: string) => <Text type="secondary">{value}</Text>,
      },
      {
        title: "Giá trị",
        dataIndex: "value",
        key: "value",
        render: (value: ReactNode) => <Text strong>{value}</Text>,
      },
    ],
    [],
  );

  const columns = useMemo<TableColumnsType<ProductionRecord>>(() => {
    const recordColumn: TableColumnsType<ProductionRecord>[number] = {
      title: "Mã / tên",
      dataIndex: "name",
      key: "name",
      fixed: isMobile ? undefined : "left",
      width: 210,
      render: (_, record) => (
        <Space orientation="vertical" size={0}>
          <Text strong>{record.name}</Text>
          <Text type="secondary">{record.code}</Text>
        </Space>
      ),
    };

    const qualityColumn: TableColumnsType<ProductionRecord>[number] = {
      title: "Chất lượng",
      dataIndex: "quality",
      key: "quality",
      width: 120,
      render: renderQualityTag,
    };

    const statusColumn: TableColumnsType<ProductionRecord>[number] = {
      title: "Trạng thái",
      dataIndex: "status",
      key: "status",
      width: 140,
      render: renderStatusTag,
    };

    const actionColumn: TableColumnsType<ProductionRecord>[number] = {
      title: "Thao tác",
      key: "actions",
      fixed: isMobile ? undefined : "right",
      width: activeStage === "ovenDried" || activeStage === "bales" ? 180 : 92,
      render: (_, record) => (
        <Space>
          <Tooltip title="Xem chi tiết">
            <Button
              aria-label="Xem chi tiết"
              icon={<EyeOutlined />}
              onClick={() => setDetailRecord(record)}
            />
          </Tooltip>
          {/* {activeStage === "ovenDried" && (
            <Tooltip title="Lấy tờ từ máy sấy để ép bành">
              <Button
                type="primary"
                icon={<CompressOutlined />}
                onClick={() => handlePreparePress(record)}>
                {isMobile ? null : "Ép bành"}
              </Button>
            </Tooltip>
          )}
          {activeStage === "bales" && (
            <Tooltip title="Chọn bành này để đóng pallet">
              <Button
                type="primary"
                icon={<GiftOutlined />}
                onClick={() => handlePreparePallet(record)}>
                {isMobile ? null : "Đóng pallet"}
              </Button>
            </Tooltip>
          )} */}
        </Space>
      ),
    };

    if (activeStage === "tanks") {
      return [
        recordColumn,
        {
          title: "Khối lượng",
          key: "volume",
          width: 230,
          render: (_, record) => (
            <Space orientation="vertical" size={4} style={{ width: "100%" }}>
              <Text>{`${formatKg(record.volumeKg)} / ${formatKg(
                record.capacityKg,
              )}`}</Text>
              <Progress
                percent={record.progress}
                size="small"
                status={record.status === "attention" ? "exception" : "active"}
              />
            </Space>
          ),
        },
        {
          title: "DRC",
          dataIndex: "drc",
          key: "drc",
          align: "right",
          width: 90,
          render: formatPercent,
        },
        {
          title: "pH",
          dataIndex: "ph",
          key: "ph",
          align: "right",
          width: 80,
          render: (value?: number) =>
            value === undefined ? "-" : numberFormatter.format(value),
        },
        qualityColumn,
        statusColumn,
        {
          title: "Vị trí",
          dataIndex: "location",
          key: "location",
          width: 160,
        },
        actionColumn,
      ];
    }

    if (activeStage === "troughs") {
      return [
        recordColumn,
        {
          title: "Bồn nguồn",
          dataIndex: "source",
          key: "source",
          width: 130,
        },
        {
          title: "Số tờ dự kiến",
          dataIndex: "sheetCount",
          key: "sheetCount",
          align: "right",
          width: 130,
          render: formatSheets,
        },
        {
          title: "Khối lượng",
          dataIndex: "weightKg",
          key: "weightKg",
          align: "right",
          width: 120,
          render: formatKg,
        },
        {
          title: "Tiến độ",
          dataIndex: "progress",
          key: "progress",
          width: 160,
          render: (value?: number) => <Progress percent={value} size="small" />,
        },
        qualityColumn,
        statusColumn,
        actionColumn,
      ];
    }

    if (activeStage === "rolledSheets") {
      return [
        recordColumn,
        {
          title: "Mương",
          dataIndex: "source",
          key: "source",
          width: 120,
        },
        {
          title: "Số tờ",
          dataIndex: "sheetCount",
          key: "sheetCount",
          align: "right",
          width: 110,
          render: formatSheets,
        },
        {
          title: "Khối lượng",
          dataIndex: "weightKg",
          key: "weightKg",
          align: "right",
          width: 120,
          render: formatKg,
        },
        {
          title: "Độ ẩm",
          dataIndex: "moisture",
          key: "moisture",
          align: "right",
          width: 100,
          render: formatPercent,
        },
        qualityColumn,
        statusColumn,
        actionColumn,
      ];
    }

    if (activeStage === "airDried") {
      return [
        recordColumn,
        {
          title: "Lô cán",
          dataIndex: "source",
          key: "source",
          width: 140,
        },
        {
          title: "Sào",
          dataIndex: "rackCount",
          key: "rackCount",
          align: "right",
          width: 90,
          render: (value?: number) =>
            value === undefined ? "-" : `${value} sào`,
        },
        {
          title: "Ngày phơi",
          dataIndex: "dryDays",
          key: "dryDays",
          align: "right",
          width: 110,
          render: (value?: number) =>
            value === undefined ? "-" : `${value} ngày`,
        },
        {
          title: "Độ ẩm",
          dataIndex: "moisture",
          key: "moisture",
          align: "right",
          width: 100,
          render: formatPercent,
        },
        {
          title: "Tiến độ",
          dataIndex: "progress",
          key: "progress",
          width: 150,
          render: (value?: number) => <Progress percent={value} size="small" />,
        },
        qualityColumn,
        statusColumn,
        actionColumn,
      ];
    }

    if (activeStage === "ovenDried") {
      return [
        recordColumn,
        {
          title: "Máy sấy / sào",
          dataIndex: "source",
          key: "source",
          width: 190,
        },
        {
          title: "Tờ có thể ép",
          dataIndex: "availableSheets",
          key: "availableSheets",
          align: "right",
          width: 130,
          render: formatSheets,
        },
        {
          title: "Khối lượng",
          dataIndex: "weightKg",
          key: "weightKg",
          align: "right",
          width: 120,
          render: formatKg,
        },
        {
          title: "Độ ẩm",
          dataIndex: "moisture",
          key: "moisture",
          align: "right",
          width: 100,
          render: formatPercent,
        },
        qualityColumn,
        statusColumn,
        actionColumn,
      ];
    }

    if (activeStage === "bales") {
      return [
        recordColumn,
        {
          title: "Mẻ sấy",
          dataIndex: "source",
          key: "source",
          width: 140,
        },
        {
          title: "Số tờ ép",
          dataIndex: "sheetCount",
          key: "sheetCount",
          align: "right",
          width: 120,
          render: formatSheets,
        },
        {
          title: "Khối lượng bành",
          dataIndex: "weightKg",
          key: "weightKg",
          align: "right",
          width: 140,
          render: formatKg,
        },
        qualityColumn,
        statusColumn,
        {
          title: "Máy ép",
          dataIndex: "location",
          key: "location",
          width: 130,
        },
        actionColumn,
      ];
    }

    return [
      recordColumn,
      {
        title: "Nguồn",
        dataIndex: "source",
        key: "source",
        width: 120,
      },
      {
        title: "Số bành",
        dataIndex: "baleCount",
        key: "baleCount",
        align: "right",
        width: 110,
        render: (value?: number) =>
          value === undefined ? "-" : `${value} bành`,
      },
      {
        title: "Khối lượng",
        dataIndex: "weightKg",
        key: "weightKg",
        align: "right",
        width: 130,
        render: formatKg,
      },
      qualityColumn,
      statusColumn,
      {
        title: "Kho",
        dataIndex: "location",
        key: "location",
        width: 160,
      },
      actionColumn,
    ];
  }, [activeStage, handlePreparePallet, handlePreparePress, isMobile]);

  const tabItems = useMemo(
    () =>
      STAGES.map((stage) => ({
        key: stage.key,
        label: (
          <Space size={6}>
            {stage.icon}
            <span>{isMobile ? stage.shortLabel : stage.label}</span>
            <Badge
              count={recordsByStage[stage.key].length}
              size="small"
              style={{ backgroundColor: "#1677ff" }}
            />
          </Space>
        ),
      })),
    [isMobile, recordsByStage],
  );

  return (
    <Space orientation="vertical" size={16} style={{ width: "100%" }}>
      <Row gutter={[12, 12]}>
        {summaryItems.map((item) => (
          <Col xs={24} sm={12} xl={6} key={item.title}>
            <Card styles={{ body: { padding: isMobile ? 12 : 16 } }}>
              <Statistic
                title={item.title}
                value={item.value}
                prefix={item.icon}
              />
            </Card>
          </Col>
        ))}
      </Row>

      <Card styles={{ body: { padding: isMobile ? 12 : 16 } }}>
        <Tabs
          activeKey={activeStage}
          items={tabItems}
          onChange={(key) => {
            setActiveStage(key as StageKey);
            handleResetFilters();
          }}
          tabBarGutter={isMobile ? 8 : 18}
        />

        <Flex
          align={isMobile ? "stretch" : "center"}
          justify="space-between"
          gap={12}
          vertical={isMobile}
          style={{ marginBottom: 16 }}>
          <Space orientation="vertical" size={0}>
            <Text strong>{activeMeta.label}</Text>
            <Text type="secondary">{activeMeta.description}</Text>
          </Space>

          <Flex wrap="wrap" gap={8} align="center">
            <Input
              allowClear
              prefix={<SearchOutlined />}
              placeholder="Tìm theo mã, tên, vị trí..."
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              style={{ width: isMobile ? "100%" : 260 }}
            />
            <Select
              allowClear
              placeholder="Chất lượng"
              options={qualityOptions}
              value={qualityFilter}
              onChange={(value) =>
                setQualityFilter(value as QualityGrade | undefined)
              }
              style={{ minWidth: isMobile ? "calc(50% - 4px)" : 140 }}
            />
            <Select
              allowClear
              placeholder="Trạng thái"
              options={statusOptions}
              value={statusFilter}
              onChange={(value) =>
                setStatusFilter(value as ProductionStatus | undefined)
              }
              suffixIcon={<FilterOutlined />}
              style={{ minWidth: isMobile ? "calc(50% - 4px)" : 150 }}
            />
            <Tooltip title="Xóa bộ lọc">
              <Button icon={<ReloadOutlined />} onClick={handleResetFilters} />
            </Tooltip>
          </Flex>
        </Flex>

        {/* {activeStage === "bales" && (
          <Card
            size="small"
            style={{ marginBottom: 16 }}
            styles={{ body: { padding: isMobile ? 12 : 16 } }}>
            <Form
              form={pressForm}
              layout="vertical"
              onFinish={handleCreateBale}>
              <Row gutter={[12, 0]}>
                <Col xs={24} md={8} xl={6}>
                  <Form.Item
                    label="Đầu ra máy sấy"
                    name="sourceBatch"
                    rules={[
                      { required: true, message: "Vui lòng chọn mẻ sấy" },
                    ]}>
                    <Select
                      showSearch
                      placeholder="Chọn mẻ sấy"
                      optionFilterProp="label"
                      options={recordsByStage.ovenDried.map((record) => ({
                        label: `${record.code} - ${record.name}`,
                        value: record.code,
                      }))}
                    />
                  </Form.Item>
                </Col>
                <Col xs={12} md={4} xl={3}>
                  <Form.Item
                    label="Số tờ lấy ra"
                    name="sheetCount"
                    rules={[
                      { required: true, message: "Nhập số tờ" },
                      {
                        type: "number",
                        min: 1,
                        message: "Số tờ phải lớn hơn 0",
                      },
                    ]}>
                    <InputNumber min={1} style={{ width: "100%" }} />
                  </Form.Item>
                </Col>
                <Col xs={12} md={4} xl={3}>
                  <Form.Item
                    label="Grade"
                    name="grade"
                    rules={[{ required: true, message: "Chọn grade" }]}>
                    <Select
                      options={[
                        { label: "RSS 1", value: "RSS 1" },
                        { label: "RSS 2", value: "RSS 2" },
                        { label: "RSS 3", value: "RSS 3" },
                      ]}
                    />
                  </Form.Item>
                </Col>
                <Col xs={24} md={5} xl={4}>
                  <Form.Item
                    label="Máy ép"
                    name="pressMachine"
                    rules={[{ required: true, message: "Chọn máy ép" }]}>
                    <Select
                      options={[
                        { label: "Máy ép 1", value: "Máy ép 1" },
                        { label: "Máy ép 2", value: "Máy ép 2" },
                        { label: "Máy ép 3", value: "Máy ép 3" },
                      ]}
                    />
                  </Form.Item>
                </Col>
                <Col xs={24} md={7} xl={5}>
                  <Form.Item label="Mã bành" name="baleCode">
                    <Input placeholder="Tự sinh nếu bỏ trống" />
                  </Form.Item>
                </Col>
                <Col xs={24} xl={3}>
                  <Form.Item label=" ">
                    <Button
                      block
                      htmlType="submit"
                      icon={<PlusOutlined />}
                      type="primary">
                      Tạo bành
                    </Button>
                  </Form.Item>
                </Col>
              </Row>
            </Form>
          </Card>
        )} */}

        {/* {activeStage === "pallets" && (
          <Card
            size="small"
            style={{ marginBottom: 16 }}
            styles={{ body: { padding: isMobile ? 12 : 16 } }}>
            <Form
              form={palletForm}
              layout="vertical"
              onFinish={handleCreatePallet}>
              <Row gutter={[12, 0]}>
                <Col xs={24} lg={9}>
                  <Form.Item
                    label="Bành đưa vào pallet"
                    name="baleCodes"
                    rules={[{ required: true, message: "Vui lòng chọn bành" }]}>
                    <Select
                      mode="multiple"
                      maxTagCount="responsive"
                      placeholder="Chọn bành"
                      optionFilterProp="label"
                      options={allBales.map((record) => ({
                        label: `${record.code} - ${record.quality} - ${formatKg(
                          record.weightKg,
                        )}`,
                        value: record.code,
                      }))}
                    />
                  </Form.Item>
                </Col>
                <Col xs={24} md={7} lg={5}>
                  <Form.Item
                    label="Kho đóng pallet"
                    name="warehouse"
                    rules={[{ required: true, message: "Chọn kho" }]}>
                    <Select
                      options={[
                        {
                          label: "Kho thành phẩm A",
                          value: "Kho thành phẩm A",
                        },
                        {
                          label: "Kho thành phẩm B",
                          value: "Kho thành phẩm B",
                        },
                        { label: "Kho chờ xuất", value: "Kho chờ xuất" },
                      ]}
                    />
                  </Form.Item>
                </Col>
                <Col xs={24} md={7} lg={4}>
                  <Form.Item label="Mã pallet" name="palletCode">
                    <Input placeholder="Tự sinh nếu bỏ trống" />
                  </Form.Item>
                </Col>
                <Col xs={12} md={5} lg={3}>
                  <Form.Item label="Tổng bành">
                    <Input readOnly value={`${selectedBales.length} bành`} />
                  </Form.Item>
                </Col>
                <Col xs={12} md={5} lg={3}>
                  <Form.Item label="Tổng kg">
                    <Input readOnly value={formatKg(selectedBaleWeight)} />
                  </Form.Item>
                </Col>
                <Col xs={24} lg={3}>
                  <Form.Item label=" ">
                    <Button
                      block
                      htmlType="submit"
                      icon={<GiftOutlined />}
                      type="primary">
                      Đóng pallet
                    </Button>
                  </Form.Item>
                </Col>
              </Row>
            </Form>
          </Card>
        )} */}

        <Table
          columns={columns}
          dataSource={filteredRows}
          rowKey="id"
          size={isMobile ? "small" : "middle"}
          scroll={{ x: 980 }}
          pagination={{
            pageSize: isMobile ? 5 : 8,
            showSizeChanger: !isMobile,
          }}
          locale={{
            emptyText: (
              <Empty
                image={Empty.PRESENTED_IMAGE_SIMPLE}
                description="Không có dữ liệu phù hợp"
              />
            ),
          }}
        />
      </Card>

      <Drawer
        title={detailRecord?.name ?? "Chi tiết"}
        open={detailRecord !== null}
        onClose={() => setDetailRecord(null)}
        size={isMobile ? "100%" : 560}
        styles={{
          body: { padding: isMobile ? 12 : 24 },
        }}>
        {detailRecord && (
          <Table
            columns={detailColumns}
            dataSource={buildDetailRows(detailRecord)}
            pagination={false}
            rowKey="label"
            showHeader={false}
            size="small"
            bordered
          />
        )}
      </Drawer>
    </Space>
  );
}
