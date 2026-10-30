from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN

OUT = r"C:\Users\Guilh\OneDrive\Desktop\PWEB\FitLifeManager.pptx"

GREEN = RGBColor(0x46, 0x7F, 0x1F)
GREEN_LIGHT = RGBColor(0xEE, 0xF7, 0xE6)
GREEN_DARK = RGBColor(0x35, 0x63, 0x1A)
BLUE = RGBColor(0x1F, 0x3A, 0x2D)
GRAY = RGBColor(0x5B, 0x65, 0x5D)
LIGHT_GRAY = RGBColor(0x8A, 0x93, 0x8A)

prs = Presentation()
prs.slide_width = Inches(13.333)
prs.slide_height = Inches(7.5)
blank = prs.slide_layouts[6]

FONT = "Calibri"


def add_textbox(slide, left, top, width, height, paragraphs, size, bold=False, color=BLUE, align=PP_ALIGN.LEFT, first_size=None):
    shape = slide.shapes.add_textbox(left, top, width, height)
    tf = shape.text_frame
    tf.word_wrap = True
    tf.margin_left = 0
    tf.margin_right = 0
    tf.margin_top = 0
    tf.margin_bottom = 0
    tf.vertical_anchor = 1  # MIDDLE
    for i, (text, sz, b, col, al) in enumerate(paragraphs):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        p.text = text
        p.font.size = Pt(sz)
        p.font.bold = b
        p.font.color.rgb = col
        p.font.name = FONT
        p.alignment = al
    return shape


def new_slide(title=None, subtitle=None, bullets=None, footer=None, wide=False):
    slide = prs.slides.add_slide(blank)

    if title:
        add_textbox(
            slide,
            Inches(0.9) if wide else Inches(0.8),
            Inches(1.35) if not subtitle else Inches(1.9),
            Inches(11.7) if wide else Inches(11.5),
            Inches(1.3),
            [(title, 44, True, GREEN_DARK, PP_ALIGN.LEFT)],
            44,
            True,
            GREEN_DARK,
            PP_ALIGN.LEFT,
        )

    if subtitle:
        add_textbox(
            slide,
            Inches(0.8),
            Inches(2.35),
            Inches(11.7),
            Inches(0.9),
            [(subtitle, 28, False, GRAY, PP_ALIGN.LEFT)],
            28,
            False,
            GRAY,
            PP_ALIGN.LEFT,
        )

    if bullets:
        start_top = Inches(3.7)
        height = Inches(3.2)
        lines = []
        for text, size, bold, col, al in bullets:
            lines.append((text, size, bold, col, al))
        add_textbox(slide, Inches(0.8), start_top, Inches(11.7), height, lines, 26, False, BLUE, PP_ALIGN.LEFT)

    if footer:
        add_textbox(
            slide,
            Inches(0.8),
            Inches(6.9),
            Inches(11.7),
            Inches(0.4),
            [(footer, 16, False, GRAY, PP_ALIGN.LEFT)],
            16,
            False,
            GRAY,
            PP_ALIGN.LEFT,
        )
    return slide


# ---------------------------------------------------------------- Slide 1 - Capa
s1 = new_slide(
    title="FitLife Manager",
    subtitle="Plataforma Web de Gestão do FitLife Gym",
    footer="FitLife Gym  •  Programação Web",
)
tf = s1.shapes[0].text_frame
tf.paragraphs[0].font.size = Pt(56)

# ---------------------------------------------------------------- Slide 2 - Contexto
new_slide(
    title="Contexto",
    subtitle="Gestão operacional e financeira",
    bullets=[
        ("Sócios, planos e pagamentos mantidos em processos separados", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Aulas, presenças e controlos manuais em folhas de registo", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Falta de indicadores centralizados para a gestão", 26, False, BLUE, PP_ALIGN.LEFT),
    ],
    footer="FitLife Manager",
)

# ---------------------------------------------------------------- Slide 3 - Objetivos
new_slide(
    title="Objetivos",
    subtitle="O que a aplicação pretende alcançar",
    bullets=[
        ("Centralizar a informação do ginásio num único local", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Gerir sócios, planos, aulas, presenças e pagamentos", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Disponibilizar estatísticas e indicadores de gestão", 26, False, BLUE, PP_ALIGN.LEFT),
    ],
    footer="FitLife Manager",
)

# ---------------------------------------------------------------- Slide 4 - Funcionalidades
new_slide(
    title="Funcionalidades",
    subtitle="Módulos principais",
    bullets=[
        ("Dashboard: indicadores, receita mensal e gráficos", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Sócios: registo, edição, eliminação, pesquisa e filtro", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Planos: criação, contagem de sócios e receita potencial", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Aulas: gestão, instrutor, horário e capacidade", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Presenças: registo, validação de duplicados e ocupação", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Pagamentos: registar, estados e totais", 26, False, BLUE, PP_ALIGN.LEFT),
    ],
    footer="FitLife Manager",
)

# ---------------------------------------------------------------- Slide 5 - Tecnologias
new_slide(
    title="Solução e Tecnologias",
    subtitle="Stack tecnológica",
    bullets=[
        ("PHP 8 + MySQL / MariaDB (mysqli)", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Bootstrap 5, Font Awesome e Chart.js", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Arquitetura em PHP com camada de includes e consultas preparadas", 26, False, BLUE, PP_ALIGN.LEFT),
    ],
    footer="FitLife Manager",
)

# ---------------------------------------------------------------- Slide 6 - Demonstração
new_slide(
    title="Demonstração",
    subtitle="Fluxo principal",
    bullets=[
        ("Adicionar sócio e atribuir plano", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Registar pagamento", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Registar presença", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Consultar dashboard e indicadores", 26, False, BLUE, PP_ALIGN.LEFT),
    ],
    footer="FitLife Manager",
)

# ---------------------------------------------------------------- Slide 7 - Conclusão
new_slide(
    title="Conclusão",
    subtitle="Próximos passos",
    bullets=[
        ("Plataforma completa com dados de demonstração", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Autenticação de funcionários", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Exportação de relatórios (CSV/PDF)", 26, False, BLUE, PP_ALIGN.LEFT),
        ("Estados de sócios e alertas de pagamento", 26, False, BLUE, PP_ALIGN.LEFT),
    ],
    footer="FitLife Manager",
)

prs.save(OUT)
print("Guardado:", OUT)
print("Total de slides:", len(prs.slides))
