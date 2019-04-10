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

def replaceEscapeSequence(match):
    number = int(match.group(1))
    return chr(number)


def checkvar(argtype, value):
    if argtype != 'var':
        err_xml_structure("Error: Argument type is not var!")
    if not re.match(Regex.var, value):
        err_xml_structure("Error: Argument var lexical error!")


def checkint(argtype, value):
    if argtype != 'int':
        err_xml_structure("Error: Argument type is not int!")
    if not re.match(Regex.integer, value):
        err_xml_structure("Error: Argument int lexical error!")


def checkbool(argtype, value):
    if argtype != 'bool':
        err_xml_structure("Error: Argument type is not bool!")
    if not re.match(Regex.boolean, value):
        err_xml_structure("Error: Argument bool lexical error!")


def checklabel(argtype, value):
    if argtype != 'label':
        err_xml_structure("Error: Argument type is not label!")
    if not re.match(Regex.label, value):
        err_xml_structure("Error: Argument label lexical error!")


def checkstring(argtype, value):
    if argtype != 'string':
        err_xml_structure("Error: Argument type is not symb!")
    if not re.match(Regex.string, value):
        err_xml_structure("Error: Argument symb lexical error!")


def checknil(argtype, value):
    if argtype != 'nil':
        err_xml_structure("Error: Argument type is not nil!")
    if not re.match(Regex.nil, value):
        err_xml_structure("Error: Argument nil lexical error!")


def checktype(argtype, value):
    if argtype != 'type':
        err_xml_structure("Error: Argument type is not type!")
    if not re.match(Regex.type, value):
        err_xml_structure("Error: Argument type lexical error!")


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


def checkempty(argtype, value):
    if argtype is not None:
        err_xml_structure("Error: Instruction has too many arguments")
    if value is not None:
        err_xml_structure("Error: Instruction has too many arguments")


def parse_argument_value(argtype, value):
    if argtype == 'string' and value is None:
        return ""
    else:
        return value


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


## Inštrukcia kódu IPPcode19
class Instruction:
    order: int = 0
    opcode: str = None
    arg1type: str = None
    arg2type: str = None
    arg3type: str = None
    arg1value: str = None
    arg2value: str = None
    arg3value: str = None


class Variable:
    name: str
    type: None
    value: None


class Frame:
    arrayOfVariables = []

    def def_var(self, name):

        for variable in self.arrayOfVariables:
            if variable.name == name:
                err_semantic()

        variable = Variable()
        variable.name = name
        self.arrayOfVariables.append(variable)

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

    def get_var_value(self, name):
        for variable in self.arrayOfVariables:
            if variable.name == name:
                return variable
        err_variable_existance()


## Interprét
class Interpret:
    def __init__(self):
        self.sourceFile = sys.stdin
        self.inputFile = sys.stdin
        self.parse_arguments()
        self.xml_read()
        self.instructions = []
        self.parse_instructions()
        self.frameStack = []
        self.tempframe: Frame
        self.localframe: Frame
        self.globalframe: Frame = Frame()
        self.stack = []
        for instruction in self.instructions:
            self.interpret_instruction(instruction)

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
                err_xml_structure("Error: Iot instruction element - found: " + elem.tag)
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
            self.instructions.insert(instruction.order, instruction)
            self.parse_instruction_arguments(instruction, elem)
            instructionOrderList.append(instruction.order)
            self.identify_instruction(instruction)

    def parse_instruction_arguments(self, instruction, arguments):
        for argument in arguments:
            if argument.tag == 'arg1':
                instruction.arg1type = argument.get('type')
                instruction.arg1value = parse_argument_value(instruction.arg1type, argument.text)
            elif argument.tag == 'arg2':
                instruction.arg2type = argument.get('type')
                instruction.arg2value = parse_argument_value(instruction.arg2type, argument.text)
            elif argument.tag == 'arg3':
                instruction.arg3type = argument.get('type')
                instruction.arg3value = parse_argument_value(instruction.arg3type, argument.text)
            else:
                err_xml_structure("Error: invalid xml instruction argument - arg4+")

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

    def interpret_instruction(self, instruction):
        if instruction.opcode == 'MOVE':
            symbvalue = self.get_symb_value(instruction.arg2type, instruction.arg2value)
            symbtype = type(symbvalue)
            localvar = instruction.arg1value[3:]
            if instruction.arg1value[0] == 'L':
                if self.localframe is None:
                    err_frame_existance()
                else:
                    self.localframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
            elif instruction.arg1value[0] == 'G':
                if self.globalframe is None:
                    err_frame_existance()
                else:
                    self.globalframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
            elif instruction.arg1value[0] == 'T':
                if self.tempframe is None:
                    err_frame_existance()
                else:
                    self.tempframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
        elif instruction.opcode == 'CREATEFRAME':
            self.tempframe = Frame()
        elif instruction.opcode == 'PUSHFRAME':
            if self.tempframe is None:
                err_frame_existance()
            if self.localframe is not None:
                self.frameStack.append(self.tempframe)
            self.localframe = self.tempframe
            self.tempframe = None
        elif instruction.opcode == 'POPFRAME':
            if self.localframe is None:
                err_frame_existance()
            else:
                self.tempframe = self.localframe
            if self.frameStack.count() > 0:
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
            if not isinstance(symbvalue, bool):
                print(symbvalue, end='')
            else:
                if symbvalue == True:
                    print('true', end='')
                else:
                    print('false', end='')
        elif instruction.opcode == 'TYPE':
            symbvalue = type(self.get_symb_value(instruction.arg2type, instruction.arg2value))
            if symbvalue is None:
                symbvalue = "nil"
            elif symbvalue is str:
                symbvalue = "string"
            elif symbvalue is int:
                symbvalue = "int"
            elif symbvalue is bool:
                symbvalue = "bool"
            symbtype = type("")
            localvar = instruction.arg1value[3:]
            if instruction.arg1value[0] == 'L':
                if self.localframe is None:
                    err_frame_existance()
                else:
                    self.localframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
            elif instruction.arg1value[0] == 'G':
                if self.globalframe is None:
                    err_frame_existance()
                else:
                    self.globalframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
            elif instruction.arg1value[0] == 'T':
                if self.tempframe is None:
                    err_frame_existance()
                else:
                    self.tempframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
        elif instruction.opcode == 'PUSHS':
            symbvalue = type(self.get_symb_value(instruction.arg1type, instruction.arg1value))
            self.stack.append(symbvalue)
        elif instruction.opcode == 'POPS':
            if self.stack.count() == 0:
                err_missing_value()
            symbvalue = self.stack.pop()
            symbtype = type(symbvalue)
            localvar = instruction.arg1value[3:]
            if instruction.arg1value[0] == 'L':
                if self.localframe is None:
                    err_frame_existance()
                else:
                    self.localframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
            elif instruction.arg1value[0] == 'G':
                if self.globalframe is None:
                    err_frame_existance()
                else:
                    self.globalframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)
            elif instruction.arg1value[0] == 'T':
                if self.tempframe is None:
                    err_frame_existance()
                else:
                    self.tempframe.set_var_value(name=localvar, type=symbtype, value=symbvalue)

        '''
        elif instruction.opcode == 'RETURN':
        elif instruction.opcode == 'BREAK':
        elif instruction.opcode == 'INT2CHAR':
        elif instruction.opcode == 'STRLEN':
        elif instruction.opcode == 'NOT':
        elif instruction.opcode == 'POPS':
        elif instruction.opcode == 'PUSHS':
        elif instruction.opcode == 'EXIT':
        elif instruction.opcode == 'CALL':
        elif instruction.opcode == 'LABEL':
        elif instruction.opcode == 'JUMP':
        elif instruction.opcode == 'READ':
        elif instruction.opcode == 'ADD':
        elif instruction.opcode == 'SUB':
        elif instruction.opcode == 'MUL':
        elif instruction.opcode == 'IDIV':
        elif instruction.opcode == 'AND':
        elif instruction.opcode == 'OR':
        elif instruction.opcode == 'LT':
        elif instruction.opcode == 'GT':
        elif instruction.opcode == 'EQ':
        elif instruction.opcode == 'STRI2INT':
        elif instruction.opcode == 'CONCAT':
        elif instruction.opcode == 'GETCHAR':
        elif instruction.opcode == 'SETCHAR':
        elif instruction.opcode == 'JUMPIFEQ':
        elif instruction.opcode == 'JUMPIFNEQ':
        else:
            print("Ty píčo, tudle neznám!")
            exit(-99)
            '''


Interpret()
