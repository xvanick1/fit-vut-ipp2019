# Created by PyCharm.
# Author: Jozef Vanický
# VUT Login: xvanic09
# Date: 2019-04-06
# Author's comment: Tento skript je upravenou kópiou kódu, ktorý som napísal pred rokom k projektu z predmetu IPP 2017/2018 k jazyku IPPcode18.
# Verze Pythonu 3.6

import sys
import argparse
import xml.etree.ElementTree as XMLElemTree
import re
import copy

## Pole inštrukcií jazyka IPPcode19
instructionList = ["MOVE", "CREATEFRAME", "PUSHFRAME", "POPFRAME", "DEFVAR", "CALL", "RETURN", "PUSHS", "POPS", "ADD",
                   "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "NOT", "INT2CHAR", "STRI2INT", "READ", "WRITE",
                   "CONCAT", "STRLEN", "GETCHAR", "SETCHAR", "TYPE", "LABEL", "JUMP", "JUMPIFEQ", "JUMPIFNEQ", "EXIT",
                   "DPRINT", "BREAK"]


## Regulárne výrazy použité pri spracovaní argumentov jednotlivých inštrukcií
class Regex:
    var = r"^(GF|LF|TF)@([A-Za-z\-\_\*\$%&][\w\-\*\_\$%&]*)$"
    integer = r"^[+-]?\d+$"
    boolean = r"^(true|false)$"
    label = r"^[A-Za-z\-\_\*\$%&][\w\-\_\*\$%&]*$"
    string = r"^((\x5C\d{3})|[^\x23\s\x5C])*$"
    symb = r"var|string|bool|int|nil"
    type = r"int|string|bool"
    nil = r"nil"
    order = r"^[0-9]+$"


##Nahradi escape sekvenciu za tisknutelný znak
def replaceEscapeSequence(match):
    number = int(match.group(1))
    return chr(number)


## Kontrola typu argumentu inštrukcie
def checkvar(argtype, value):
    if argtype != 'var':
        err_xml_structure("Error: Argument type is not var!")
    if not re.match(Regex.var, value):
        err_xml_structure("Error: Argument var lexical error!")


## Kontrola typu argumentu inštrukcie
def checkint(argtype, value):
    if argtype != 'int':
        err_xml_structure("Error: Argument type is not int!")
    if not re.match(Regex.integer, value):
        err_xml_structure("Error: Argument int lexical error!")


## Kontrola typu argumentu inštrukcie
def checkbool(argtype, value):
    if argtype != 'bool':
        err_xml_structure("Error: Argument type is not bool!")
    if not re.match(Regex.boolean, value):
        err_xml_structure("Error: Argument bool lexical error!")


## Kontrola typu argumentu inštrukcie
def checklabel(argtype, value):
    if argtype != 'label':
        err_xml_structure("Error: Argument type is not label!")
    if not re.match(Regex.label, value):
        err_xml_structure("Error: Argument label lexical error!")


## Kontrola typu argumentu inštrukcie
def checkstring(argtype, value):
    if argtype != 'string':
        err_xml_structure("Error: Argument type is not symb!")
    if not re.match(Regex.string, value):
        err_xml_structure("Error: Argument symb lexical error!")


## Kontrola typu argumentu inštrukcie
def checknil(argtype, value):
    if argtype != 'nil':
        err_xml_structure("Error: Argument type is not nil!")
    if not re.match(Regex.nil, value):
        err_xml_structure("Error: Argument nil lexical error!")


## Kontrola typu argumentu inštrukcie
def checktype(argtype, value):
    if argtype != 'type':
        err_xml_structure("Error: Argument type is not type!")
    if not re.match(Regex.type, value):
        err_xml_structure("Error: Argument type lexical error!")


## Kontrola typu argumentu inštrukcie
def checksymb(argtype, value):
    if argtype == 'var':
        checkvar(argtype, value)
    elif argtype == 'string':
        checkstring(argtype, value)
    elif argtype == 'bool':
        checkbool(argtype, value)
    elif argtype == 'int':
        checkint(argtype, value)
    elif argtype == 'nil':
        checknil(argtype, value)
    else:
        err_xml_structure("Error: Argument type lexical error: expected symb!")


## Kontrola neprázdnosti argumentu inštrukcie
def checkempty(argtype, value):
    if argtype is not None:
        err_xml_structure("Error: Instruction has too many arguments")
    if value is not None:
        err_xml_structure("Error: Instruction has too many arguments")


## Vráti hodnotu argumentu
def parse_argument_value(argtype, value):
    if argtype == 'string' and value is None:
        return ""
    else:
        return value


## Pomocné metody jednotlivých chybových kodov
def err_script_argument(text):
    print(text, file=sys.stderr)
    exit(10)


def err_input_file(text):
    print(text, file=sys.stderr)
    exit(11)


def err_well_formated_xml():
    print("Error: XML is not well formatted!", file=sys.stderr)
    exit(31)


def err_xml_structure(text):
    print(text, file=sys.stderr)
    exit(32)


def err_semantic():
    print("Error: Semantic error!", file=sys.stderr)
    exit(52)


def err_bad_operand():
    print("Error: Wrong operand type!", file=sys.stderr)
    exit(53)


def err_variable_existance():
    print("Error: Variable doesn't exist!", file=sys.stderr)
    exit(54)


def err_frame_existance():
    print("Error: Frame doesn't exist!", file=sys.stderr)
    exit(55)


def err_missing_value():
    print("Error: Missing value!", file=sys.stderr)
    exit(56)


def err_operand_value():
    print("Error: Wrong operand value!", file=sys.stderr)
    exit(57)


def err_string():
    print("Error: Bad string!", file=sys.stderr)
    exit(58)


## Trieda inštrukcie kódu IPPcode19
class Instruction:
    order: int = 0
    opcode: str = None
    arg1type: str = None
    arg2type: str = None
    arg3type: str = None
    arg1value: str = None
    arg2value: str = None
    arg3value: str = None


## Trieda premennej argumentu
class Variable:
    name: str
    type = None
    value = None


## Trieda rámca
class Frame:
    arrayOfVariables = []

    ### Zadefinovanie premennej
    def def_var(self, name):

        for variable in self.arrayOfVariables:
            if variable.name == name:
                err_semantic()

        variable = Variable()
        variable.name = name
        variable.type = None
        variable.type = None
        self.arrayOfVariables.append(variable)

    ### Nastavenie hodnoty premennej
    def set_var_value(self, name, type, value):
        tempvar = None

        for variable in self.arrayOfVariables:
            if variable.name == name:
                tempvar = variable
                break
        if tempvar is None:
            err_variable_existance()

        tempvar.type = type
        tempvar.value = value

    ### Získanie hodnoty premennej
    def get_var_value(self, name):
        for variable in self.arrayOfVariables:
            if variable.name == name:
                return variable
        err_variable_existance()


## Interprét
class Interpret:
    def __init__(self):
        ## Spracovanie vstupných argumentov
        self.sourceFile = sys.stdin
        self.inputFile = sys.stdin
        self.parse_arguments()

        ## Načítanie inštrukcií z XML reprezentácie
        self.xml_read()
        self.instructions = []
        self.parse_instructions()
        self.instructions.sort(key=self.sort_order)

        ## Inicializacie struktur
        self.frameStack = []
        self.tempframe = None
        self.localframe = None
        self.globalframe: Frame = Frame()
        self.stack = []
        self.pc_counter = 0
        self.pc_stack = []
        self.labels = {}

        ## Interpretacie inštrukcií
        self.interpret_labels()
        self.pc_counter = 0
        while self.pc_counter < len(self.instructions):
            self.interpret_instruction(self.instructions[self.pc_counter])
            self.pc_counter += 1

    ### Spracovanie vstupných argumentov
    def parse_arguments(self):
        parser = argparse.ArgumentParser(
            description="Napoveda k intepret.py. Interpret jazyka IPPcode19, vstupni format XML.",
            epilog="Musi byt zadan alespon jeden z techto argumentu.")
        parser.add_argument('-s', '--source', help='zdrojovy soubor ve formatu XML (jinak stdin)', metavar="sourceFile",
                            required=False)
        parser.add_argument('-i', '--input', help='vstupni soubor (jinak stdin)', metavar='inputFile', required=False)

        if ("--help" in sys.argv) and len(sys.argv) > 2:
            err_script_argument("Error: Wrong input!")
        try:
            args = parser.parse_args()
        except:
            if ("--help" in sys.argv) and len(sys.argv) == 2:
                exit(0)
            else:
                exit(10)  # no print !!!

        if not args.source and not args.input:
            err_script_argument("Error: --input or --source required!")
        else:
            if args.source:
                self.sourceFile = args.source
            if args.input:
                try:
                    self.inputFile = open(args.input)
                except:
                    err_input_file("Error: Input file not found or insufficient permissions!")

    ### Načítanie XML reprezentácie zo vstupu
    def xml_read(self):
        try:
            xmlObject = XMLElemTree.parse(self.sourceFile)
            self.root = xmlObject.getroot()
        except FileNotFoundError:
            err_input_file("Error: Source file not found!")
        except:
            err_well_formated_xml()

    ### Parsovanie inštrukcií z XML reprezentácie
    def parse_instructions(self):
        instructionOrderList = []
        xmlProgramAttribs = ['language', 'name', 'description']
        for attrib in self.root.attrib:
            if attrib not in xmlProgramAttribs:
                err_xml_structure("Error: Unknown attribute " + attrib + " in Program!")
        if self.root.tag != 'program' or self.root.get('language') != 'IPPcode19':
            err_xml_structure("Error: Not valid XML for IPPcode19!")
        for elem in self.root:
            instruction = Instruction()
            if elem.tag != 'instruction':
                err_xml_structure("Error: Not instruction element - found: " + elem.tag)
            if elem.get('order') is None:
                err_xml_structure("Error: Order not defined")
            if re.match(Regex.order, elem.get('order')) is None:
                err_xml_structure("Error: Order is not number")
            if elem.get('opcode') is None:
                err_xml_structure("Error: Opcode not defined")
            instruction.opcode = str(elem.get('opcode')).upper()
            if not instruction.opcode in instructionList:
                err_xml_structure("Error: Unknown instruction: " + instruction.opcode)
            if int(elem.get('order')) in instructionOrderList:
                err_xml_structure("Error: Duplicate order")
            instruction.order = int(elem.get('order'))
            self.instructions.append(instruction)
            self.parse_instruction_arguments(instruction, elem)
            instructionOrderList.append(instruction.order)
            self.identify_instruction(instruction)

    ### Získanie hodnoty orderu inštrukcie
    def sort_order(self, instruction):
        return instruction.order

    ### Parsovanie argumentov inštrukcie z XML
    def parse_instruction_arguments(self, instruction, arguments):
        for argument in arguments:
            if argument.tag == 'arg1':
                if instruction.arg1type is not None or argument.get('type') is None:
                    err_xml_structure("Error: arg1 error")
                instruction.arg1type = argument.get('type')
                instruction.arg1value = parse_argument_value(instruction.arg1type, argument.text)
            elif argument.tag == 'arg2':
                if instruction.arg2type is not None or argument.get('type') is None:
                    err_xml_structure("Error: arg2 error")
                instruction.arg2type = argument.get('type')
                instruction.arg2value = parse_argument_value(instruction.arg2type, argument.text)
            elif argument.tag == 'arg3':
                if instruction.arg3type is not None or argument.get('type') is None:
                    err_xml_structure("Error: arg3 error")
                instruction.arg3type = argument.get('type')
                instruction.arg3value = parse_argument_value(instruction.arg3type, argument.text)
            else:
                err_xml_structure("Error: invalid xml instruction argument - arg4+")

    ### Syntaktická a lexikálná analýza inštrukcie
    def identify_instruction(self, instruction):
        if instruction.opcode == 'MOVE' or instruction.opcode == 'INT2CHAR' or instruction.opcode == 'STRLEN' or instruction.opcode == 'TYPE' or instruction.opcode == 'NOT':
            checkvar(instruction.arg1type, instruction.arg1value)
            checksymb(instruction.arg2type, instruction.arg2value)
            checkempty(instruction.arg3type, instruction.arg3value)
        elif instruction.opcode == 'CREATEFRAME' or instruction.opcode == 'PUSHFRAME' or instruction.opcode == 'POPFRAME' or instruction.opcode == 'RETURN' or instruction.opcode == 'BREAK':
            checkempty(instruction.arg1type, instruction.arg1value)
            checkempty(instruction.arg2type, instruction.arg2value)
            checkempty(instruction.arg3type, instruction.arg3value)
        elif instruction.opcode == 'DEFVAR' or instruction.opcode == 'POPS':
            checkvar(instruction.arg1type, instruction.arg1value)
            checkempty(instruction.arg2type, instruction.arg2value)
            checkempty(instruction.arg3type, instruction.arg3value)
        elif instruction.opcode == 'PUSHS' or instruction.opcode == 'WRITE' or instruction.opcode == 'EXIT' or instruction.opcode == 'DPRINT':
            checksymb(instruction.arg1type, instruction.arg1value)
            checkempty(instruction.arg2type, instruction.arg2value)
            checkempty(instruction.arg3type, instruction.arg3value)
        elif instruction.opcode == 'CALL' or instruction.opcode == 'LABEL' or instruction.opcode == 'JUMP':
            checklabel(instruction.arg1type, instruction.arg1value)
            checkempty(instruction.arg2type, instruction.arg2value)
            checkempty(instruction.arg3type, instruction.arg3value)
        elif instruction.opcode == 'READ':
            checkvar(instruction.arg1type, instruction.arg1value)
            checktype(instruction.arg2type, instruction.arg2value)
            checkempty(instruction.arg3type, instruction.arg3value)
        elif instruction.opcode == 'ADD' or instruction.opcode == 'SUB' or instruction.opcode == 'MUL' or instruction.opcode == 'IDIV' or instruction.opcode == 'AND' or instruction.opcode == 'OR' or instruction.opcode == 'LT' or instruction.opcode == 'GT' or instruction.opcode == 'EQ' or instruction.opcode == 'STRI2INT' or instruction.opcode == 'CONCAT' or instruction.opcode == 'GETCHAR' or instruction.opcode == 'SETCHAR':
            checkvar(instruction.arg1type, instruction.arg1value)
            checksymb(instruction.arg2type, instruction.arg2value)
            checksymb(instruction.arg3type, instruction.arg3value)
        elif instruction.opcode == 'JUMPIFEQ' or instruction.opcode == 'JUMPIFNEQ':
            checklabel(instruction.arg1type, instruction.arg1value)
            checksymb(instruction.arg2type, instruction.arg2value)
            checksymb(instruction.arg3type, instruction.arg3value)

    ### Sémantická kontrola symbolu
    def check_same_symb(self, symb1value, symb2value):
        if symb1value is None and symb2value is not None:
            err_bad_operand()
        if symb1value is not None and symb2value is None:
            err_bad_operand()
        if type(symb1value) is not type(symb2value):
            err_bad_operand()

    ### Sémantická kontrola integeru
    def check_same_int(self, symb1value, symb2value):
        if symb1value is None:
            err_bad_operand()
        if symb2value is None:
            err_bad_operand()
        if type(symb1value) is not type(symb2value):
            err_bad_operand()
        if type(symb1value) is not int or type(symb2value) is not int:
            err_bad_operand()

    ### Sémantická kontrola reťazca
    def check_same_str(self, symb1value, symb2value):
        if symb1value is None:
            err_bad_operand()
        if symb2value is None:
            err_bad_operand()
        if type(symb1value) is not type(symb2value):
            err_bad_operand()
        if type(symb1value) is not str or type(symb2value) is not str:
            err_bad_operand()

    ### Sémantická kontrola boolu
    def check_same_bool(self, symb1value, symb2value):
        if symb1value is None:
            err_bad_operand()
        if symb2value is None:
            err_bad_operand()
        if type(symb1value) is not type(symb2value):
            err_bad_operand()
        if type(symb1value) is not bool or type(symb2value) is not bool:
            err_bad_operand()

    ### Identifikácia typu symbolu a získanie hodnoty
    def get_symb_value(self, symbtype, symbvalue):
        if symbtype == 'var':
            localvar = symbvalue[3:]
            tempvar = None
            if symbvalue[0] == 'L':
                if self.localframe is None:
                    err_frame_existance()
                else:
                    tempvar = self.localframe.get_var_value(localvar)
            elif symbvalue[0] == 'G':
                if self.globalframe is None:
                    err_frame_existance()
                else:
                    tempvar = self.globalframe.get_var_value(localvar)
            elif symbvalue[0] == 'T':
                if self.tempframe is None:
                    err_frame_existance()
                else:
                    tempvar = self.tempframe.get_var_value(localvar)
            if tempvar.value is None and tempvar.type is None:
                err_missing_value()
            return tempvar.value
        elif symbtype == 'string':
            if symbvalue is None:
                return ""
            else:
                regex = re.compile(r"\\(\d{3})")
                return regex.sub(replaceEscapeSequence, symbvalue)
        elif symbtype == 'bool':
            if symbvalue == 'true':
                return True
            else:
                return False
        elif symbtype == 'int':
            return int(symbvalue)
        elif symbtype == 'nil':
            return None

    ### Nastavenie hodnoty premennej
    def set_var(self, varname, symbvalue):
        symbtype = type(symbvalue)
        localvar = varname[3:]
        if varname[0] == 'L':
            if self.localframe is None:
                err_frame_existance()
            else:
                self.localframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
        elif varname[0] == 'G':
            if self.globalframe is None:
                err_frame_existance()
            else:
                self.globalframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
        elif varname[0] == 'T':
            if self.tempframe is None:
                err_frame_existance()
            else:
                self.tempframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)

    ### Interpretácia labelu
    def interpret_labels(self):
        for instruction in self.instructions:
            if instruction.opcode == 'LABEL':
                if instruction.arg1value in self.labels:
                    err_semantic()
                self.labels.setdefault(instruction.arg1value)
                self.labels[instruction.arg1value] = self.pc_counter
            self.pc_counter += 1

    ### Interpretácia inštrukcie - Sémantická analýza inštrukcie (detaily k nahliadnutiu v dokumentácii: ipp19spec.pdf str:11-14)
    def interpret_instruction(self, instruction):
        if instruction.opcode == 'MOVE':
            symbvalue = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            self.set_var(instruction.arg1value, symbvalue)
        elif instruction.opcode == 'CREATEFRAME':
            self.tempframe = Frame()
            self.tempframe.arrayOfVariables = []
        elif instruction.opcode == 'PUSHFRAME':
            if self.tempframe is None:
                err_frame_existance()
            if self.localframe is not None:
                self.frameStack.append(self.localframe)
            self.localframe = self.tempframe
            self.tempframe = None
        elif instruction.opcode == 'POPFRAME':
            if self.localframe is None:
                err_frame_existance()
            else:
                self.tempframe = self.localframe
                self.localframe = None
            if len(self.frameStack) > 0:
                self.localframe = self.frameStack.pop()
        elif instruction.opcode == 'DEFVAR':
            localvar = instruction.arg1value[3:]
            if instruction.arg1value[0] == 'L':
                if self.localframe is None:
                    err_frame_existance()
                else:
                    self.localframe.def_var(name=localvar)
            elif instruction.arg1value[0] == 'G':
                if self.globalframe is None:
                    err_frame_existance()
                else:
                    self.globalframe.def_var(name=localvar)
            elif instruction.arg1value[0] == 'T':
                if self.tempframe is None:
                    err_frame_existance()
                else:
                    self.tempframe.def_var(name=localvar)
        elif instruction.opcode == 'DPRINT':
            symbvalue = self.get_symb_value(instruction.arg1type, instruction.arg1value)
            print(symbvalue, file=sys.stderr)
        elif instruction.opcode == 'WRITE':
            symbvalue = self.get_symb_value(instruction.arg1type, instruction.arg1value)
            if symbvalue is None:
                print('', end='')
            elif type(symbvalue) is not bool:
                print(symbvalue, end='')
            else:
                if symbvalue == True:
                    print('true', end='')
                else:
                    print('false', end='')
        elif instruction.opcode == 'TYPE':
            if instruction.arg1type != 'var':
                val = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            else:
                localvar = instruction.arg2value[3:]
                if instruction.arg2value[0] == 'L':
                    if self.localframe is None:
                        err_frame_existance()
                    else:
                        val = self.localframe.get_var_value(localvar)
                elif instruction.arg2value[0] == 'G':
                    if self.globalframe is None:
                        err_frame_existance()
                    else:
                        val = self.globalframe.get_var_value(localvar)
                elif instruction.arg2value[0] == 'T':
                    if self.tempframe is None:
                        err_frame_existance()
                    else:
                        val = self.tempframe.get_var_value(localvar)
                if val.value is None and val.type is None:
                    self.set_var(instruction.arg1value, "")
                    return
                else:
                    val = val.value
            symbvalue = type(val)
            if val is None:
                symbvalue = "nil"
            elif symbvalue is str:
                symbvalue = "string"
            elif symbvalue is int:
                symbvalue = "int"
            elif symbvalue is bool:
                symbvalue = "bool"
            self.set_var(instruction.arg1value, symbvalue)
        elif instruction.opcode == 'PUSHS':
            symbvalue = self.get_symb_value(instruction.arg1type, instruction.arg1value)
            self.stack.append(symbvalue)
        elif instruction.opcode == 'POPS':
            if len(self.stack) == 0:
                err_missing_value()
            symbvalue = self.stack.pop()
            self.set_var(instruction.arg1value, symbvalue)
        elif instruction.opcode == 'ADD':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_int(symb1value, symb2value)
            self.set_var(instruction.arg1value, symb1value + symb2value)
        elif instruction.opcode == 'SUB':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_int(symb1value, symb2value)
            self.set_var(instruction.arg1value, symb1value - symb2value)
        elif instruction.opcode == 'MUL':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_int(symb1value, symb2value)
            self.set_var(instruction.arg1value, symb1value * symb2value)
        elif instruction.opcode == 'IDIV':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_int(symb1value, symb2value)
            if symb2value == 0:
                err_operand_value()
            self.set_var(instruction.arg1value, symb1value // symb2value)
        elif instruction.opcode == 'LT':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_symb(symb1value, symb2value)
            if symb1value is None or symb2value is None:
                err_bad_operand()
            self.set_var(instruction.arg1value, symb1value < symb2value)
        elif instruction.opcode == 'GT':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_symb(symb1value, symb2value)
            if symb1value is None or symb2value is None:
                err_bad_operand()
            self.set_var(instruction.arg1value, symb1value > symb2value)
        elif instruction.opcode == 'EQ':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            if symb1value is None and symb2value is not None:
                self.set_var(instruction.arg1value, False)
                return
            elif symb1value is not None and symb2value is None:
                self.set_var(instruction.arg1value, False)
                return
            self.check_same_symb(symb1value, symb2value)
            self.set_var(instruction.arg1value, symb1value == symb2value)
        elif instruction.opcode == 'AND':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_bool(symb1value, symb2value)
            self.set_var(instruction.arg1value, symb1value and symb2value)
        elif instruction.opcode == 'OR':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_bool(symb1value, symb2value)
            self.set_var(instruction.arg1value, symb1value or symb2value)
        elif instruction.opcode == 'NOT':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            self.check_same_bool(symb1value, True)
            self.set_var(instruction.arg1value, not symb1value)
        elif instruction.opcode == 'INT2CHAR':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            self.check_same_int(symb1value, 0)
            if symb1value < 0 or symb1value > 1114111:
                err_string()
            self.set_var(instruction.arg1value, chr(symb1value))
        elif instruction.opcode == 'STRI2INT':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_str(symb1value, "")
            self.check_same_int(symb2value, 0)
            if symb2value < 0 or symb2value >= len(symb1value):
                err_string()
            self.set_var(instruction.arg1value, ord(symb1value[symb2value]))
        elif instruction.opcode == 'READ':
            symb1value = self.inputFile.readline()
            if not symb1value:
                symb1value = ''
            if len(symb1value) > 0 and symb1value[len(symb1value) - 1] == '\n':
                symb1value = symb1value[:-1]
            if instruction.arg2value == "bool":
                if symb1value.upper() == "TRUE":
                    symb1value = True
                else:
                    symb1value = False
            elif instruction.arg2value == "int":
                try:
                    symb1value = int(symb1value)
                except:
                    symb1value = 0
            self.set_var(instruction.arg1value, symb1value)
        elif instruction.opcode == 'CONCAT':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_str(symb1value, "")
            self.check_same_str(symb2value, "")
            self.set_var(instruction.arg1value, symb1value + symb2value)
        elif instruction.opcode == 'GETCHAR':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_str(symb1value, "")
            self.check_same_int(symb2value, 0)
            if symb2value < 0 or symb2value >= len(symb1value):
                err_string()
            self.set_var(instruction.arg1value, symb1value[symb2value])
        elif instruction.opcode == 'SETCHAR':
            varvalue = self.get_symb_value(instruction.arg1type, instruction.arg1value)
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_str(varvalue, "")
            self.check_same_int(symb1value, 0)
            self.check_same_str(symb2value, "")
            if symb1value < 0 or symb1value >= len(varvalue) or len(symb2value) == 0:
                err_string()
            if symb1value > 0:
                varvalue = varvalue[:symb1value] + symb2value[0] + varvalue[1 + symb1value:]
            else:
                varvalue = symb2value[0] + varvalue[1 + symb1value:]
            self.set_var(instruction.arg1value, varvalue)
        elif instruction.opcode == 'STRLEN':
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            self.check_same_str(symb1value, "")
            self.set_var(instruction.arg1value, len(symb1value))
        elif instruction.opcode == 'EXIT':
            symb1value = self.get_symb_value(instruction.arg1type, instruction.arg1value)
            self.check_same_int(symb1value, 0)
            if symb1value < 0 or symb1value > 49:
                err_operand_value()
            exit(symb1value)
        elif instruction.opcode == 'BREAK':
            print('PC counter: ' + str(self.pc_counter), file=sys.stderr)
        elif instruction.opcode == 'LABEL':
            return
        elif instruction.opcode == 'CALL':
            if instruction.arg1value not in self.labels:
                err_semantic()
            self.pc_stack.append(self.pc_counter)
            self.pc_counter = self.labels[instruction.arg1value]
        elif instruction.opcode == 'RETURN':
            if len(self.pc_stack) == 0:
                err_missing_value()
            self.pc_counter = self.pc_stack.pop()
        elif instruction.opcode == 'JUMP':
            if instruction.arg1value not in self.labels:
                err_semantic()
            self.pc_counter = self.labels[instruction.arg1value]
        elif instruction.opcode == 'JUMPIFEQ':
            if instruction.arg1value not in self.labels:
                err_semantic()
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_symb(symb1value, symb2value)
            if symb1value == symb2value:
                self.pc_counter = self.labels[instruction.arg1value]
        elif instruction.opcode == 'JUMPIFNEQ':
            if instruction.arg1value not in self.labels:
                err_semantic()
            symb1value = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symb2value = self.get_symb_value(instruction.arg3type, instruction.arg3value)
            self.check_same_symb(symb1value, symb2value)
            if symb1value != symb2value:
                self.pc_counter = self.labels[instruction.arg1value]


Interpret()
